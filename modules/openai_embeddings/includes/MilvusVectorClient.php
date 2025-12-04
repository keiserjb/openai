<?php

require_once 'MilvusV2.php';
use GuzzleHttp\Client as GuzzleClient;

/**
 * Milvus vector client for OpenAI Embeddings (Backdrop CMS style).
 */
class MilvusVectorClient extends VectorClientBase {

  /**
   * @var MilvusV2
   */
  protected $milvus;

  /**
   * @var array
   */
  protected $settings;

  /**
   * Constructor.
   */
  public function __construct(array $settings) {
    $this->settings = $settings;
    $client = new GuzzleClient([
      'timeout' => 30,
      'http_errors' => false,
    ]);
    $this->milvus = new MilvusV2($client);

    // Support both openai_embeddings and search_api_ai settings formats
    $server = $settings['server'] ?? $settings['milvus_server'] ?? $settings['basehost'] ?? '';
    $port = $settings['port'] ?? $settings['milvus_port'] ?? '';
    $api_key = $settings['api_key'] ?? key_get_key_value($settings['milvus_token']) ?? '';

    // Ensure server URL is in the correct format for Zilliz detection
    if (!empty($server)) {
      // If the URL doesn't include the protocol and it's a Zilliz cloud URL, add https://
      if (!preg_match('~^https?://~i', $server) &&
        preg_match('~(zillizcloud\.com|cloud\.zilliz\.com)~', $server)) {
        $server = 'https://' . $server;
      }
    }

    $this->milvus->setBaseUrl($server);
    $this->milvus->setPort($port);

    if (!empty($api_key)) {
      $this->milvus->setApiKey($api_key);
    }
  }

  /**
   * Insert or update vectors.
   * Required by VectorClientBase.
   */
  public function upsert(array $parameters) {
    // Call your original upsert() logic but use $parameters.
    // Consider supporting batch upserts, if Milvus does.
    return $this->upsertInternal($parameters);
  }

  /**
   * Insert or update vectors.
   */
  public function upsertInternal(array $item) {
    $collection = $item['collection'];
    $database = isset($item['database']) ? $item['database'] : 'default';

    foreach ($item['vectors'] as $orig_vector) {
      $vector = $orig_vector;

      // Remove 'id' field if present (unless using manual PKs and autoID is off)
      if (isset($vector['id'])) {
        unset($vector['id']);
      }

      // Milvus expects 'vector', not 'values'
      if (isset($vector['values'])) {
        $vector['vector'] = $vector['values'];
        unset($vector['values']);
      }

      // Flatten 'metadata' to top-level fields (optional, but handy for filtering)
      if (isset($vector['metadata']) && is_array($vector['metadata'])) {
        foreach ($vector['metadata'] as $k => $v) {
          $vector[$k] = $v;
        }
        unset($vector['metadata']);
      }

      // Now $vector has: vector, content, entity_id, etc. — perfect for Milvus!
      $result = $this->milvus->insertIntoCollection($collection, $vector, $database);

      // If insert fails due to missing collection, try to create collection and retry ONCE.
      if (isset($result['code']) && $result['code'] == 100) {
        $dimension = isset($vector['vector']) ? count($vector['vector']) : 1536; // fallback
        $metricType = 'COSINE';

        // Ensure dimension is an integer
        $dimension = (int)$dimension;

        $create = $this->milvus->createCollection($collection, $database, $dimension, $metricType);

        // Log collection creation errors
        if (empty($create) || (isset($create['code']) && $create['code'] !== 0 && $create['code'] !== 200)) {
          watchdog('openai_embeddings', 'Failed to create Milvus collection @collection: @create', [
            '@collection' => $collection,
            '@create' => print_r($create, 1)
          ], WATCHDOG_ERROR);
          continue; // Don’t retry insert if creation failed
        } else {
          watchdog('openai_embeddings', 'Milvus collection @collection created. Retrying insert.', [
            '@collection' => $collection
          ], WATCHDOG_NOTICE);
        }

        // Try insert again, after transforming data again (in case of structure)
        $result = $this->milvus->insertIntoCollection($collection, $vector, $database);
      }

      // Log any final errors
      if (empty($result) || (isset($result['code']) && $result['code'] !== 0 && $result['code'] !== 200)) {
        watchdog('openai_embeddings', 'Milvus insert failed: @result', ['@result' => print_r($result, 1)], WATCHDOG_ERROR);
      }
    }
  }

  /**
   * Stats (placeholder).
   */
  public function stats() {
    // You can return an empty array or implement Milvus stats.
    return [];
  }

  /**
   * Search for vectors.
   */
  public function search($collection, $vector, $top_k = 10, $outputFields = ['content'], $database = 'default', $filter = '') {
    $result = $this->milvus->search(
      $collection,
      $vector,
      $outputFields,
      $filter,
      $top_k * 2, // Request more results to account for duplicates
      0,
      $database
    );
    if (!isset($result['data']) || !is_array($result['data'])) {
      return [];
    }
    //dpm($result['data']);
    // Deduplicate based on entity_id + field_name combination
    $deduplicated = [];
    $seen = [];
    foreach ($result['data'] as $item) {
      $key = NULL;

      // Try multiple formats to extract entity ID
      if (isset($item['entity_id'], $item['entity_type'])) {
        // Milvus native format
        $key = $item['entity_type'] . ':' . $item['entity_id'];
      }
      elseif (isset($item['backdrop_entity_id'])) {
        // Zilliz format with backdrop_entity_id
        $key = $item['backdrop_entity_id'];
      }
      elseif (isset($item['nid'])) {
        // Format with just nid
        $key = 'node:' . $item['nid'];
      }
      elseif (preg_match('/^entity:([^\/]+)\/(\d+)/', $item['backdrop_long_id'] ?? '', $matches)) {
        // Extract from backdrop_long_id
        $key = $matches[1] . ':' . $matches[2];
      }

      if (!$key) {
        continue;
      }

      // Add field name to key if available
      if (isset($item['field_name'])) {
        $key .= ':' . $item['field_name'];
      }

      if (!isset($seen[$key])) {
        $seen[$key] = TRUE;
        $deduplicated[] = $item;
        if (count($deduplicated) >= $top_k) {
          break;
        }
      }
    }
   //dpm($deduplicated);
    return $deduplicated;
  }


  /**
   * Metadata query helper.
   *
   * @param string $collection
   * @param array $outputFields
   * @param int $limit
   * @param string $database
   * @param string $filter
   *
   * @return array
   */
  public function queryMetadata(string $collection, array $outputFields = ['id','content'], int $limit = 10, string $database = 'default', string $filter = 'id not in [0]') {
    try {
      $result = $this->milvus->query($collection, $outputFields, $filter, $limit, 0, $database);
      // MilvusV2::query returns a decoded JSON object/array. Normalize to array.
      if (is_array($result)) {
        return $result;
      }
      if (is_object($result)) {
        // If the object contains 'data', return that; otherwise convert object to array.
        if (isset($result->data)) {
          return is_array($result->data) ? $result->data : (array) $result->data;
        }
        return (array) $result;
      }
      return [];
    }
    catch (Exception $e) {
      watchdog('openai_embeddings', 'Milvus metadata query failed: @msg', ['@msg' => $e->getMessage()], WATCHDOG_ERROR);
      return [];
    }
  }

  /**
   * Query for metadata.
   */
  /*public function query($collection, $outputFields = ['id', 'content'], $limit = 10, $database = 'default', $filter = 'id not in [0]') {
    $result = $this->milvus->query(
      $collection,
      $outputFields,
      $filter,
      $limit,
      0,
      $database
    );
    return $result['data'] ?? [];
  }*/

  public function query(array $parameters) {
    // Example logic: if a 'vector' is present, do vector search, else do metadata query.
    if (!empty($parameters['vector'])) {
      // Milvus search.
      $collection = $parameters['collection'] ?? '';
      $vector = $parameters['vector'];
      $top_k = $parameters['top_k'] ?? 10;
      $outputFields = $parameters['output_fields'] ?? ['content'];
      $database = $parameters['database'] ?? 'default';
      $filter = $parameters['filter'] ?? '';
      return $this->search($collection, $vector, $top_k, $outputFields, $database, $filter);
    }
    else {
      // Milvus metadata query.
      $collection = $parameters['collection'] ?? '';
      $outputFields = $parameters['output_fields'] ?? ['id', 'content'];
      $limit = $parameters['limit'] ?? 10;
      $database = $parameters['database'] ?? 'default';
      $filter = $parameters['filter'] ?? 'id not in [0]';
      return $this->queryMetadata($collection, $outputFields, $limit, $database, $filter);
    }
  }

  public function delete(array $parameters) {
    $collection = $parameters['collection'] ?? '';
    $ids = $parameters['ids'] ?? [];
    $database = $parameters['database'] ?? 'default';

    return $this->milvus->deleteFromCollection($collection, $ids, $database);
  }


  /**
   * List collections.
   */
  public function listCollections($database = 'default') {
    return $this->milvus->listCollections($database);
  }

  /**
   * Create collection (if needed).
   */
  public function createCollection($collection, $dimension, $metricType = 'COSINE', $database = 'default') {
    // Ensure dimension is properly typed as integer to avoid Milvus API errors
    $dimension = (int)$dimension;

    try {
      $result = $this->milvus->createCollection($collection, $database, $dimension, $metricType);
      if (isset($result['code']) && $result['code'] != 0 && $result['code'] != 200) {
        watchdog('openai_embeddings', 'Failed to create Milvus collection @collection: @error', [
          '@collection' => $collection,
          '@error' => print_r($result, TRUE)
        ], WATCHDOG_ERROR);
      }
      return $result;
    } catch (Exception $e) {
      watchdog('openai_embeddings', 'Exception creating Milvus collection @collection: @message', [
        '@collection' => $collection,
        '@message' => $e->getMessage()
      ], WATCHDOG_ERROR);
      return ['code' => 1100, 'message' => $e->getMessage()];
    }
  }

  /**
   * Describe a collection.
   */
  public function describeCollection($database, $collection) {
    return $this->milvus->describeCollection($database, $collection);
  }

  /**
   * Test the Milvus connection.
   *
   * @return array
   *   ['success' => bool, 'message' => string, 'collections' => array|null]
   */
  public function testConnection(): array {
    try {
      $collections = $this->listCollections();
      if (empty($collections) || !is_array($collections)) {
        return [
          'success' => TRUE, // Milvus may return empty but connection worked
          'message' => 'Connected to Milvus; no collections returned or empty list.',
          'collections' => is_array($collections) ? $collections : [],
        ];
      }

      return [
        'success' => TRUE,
        'message' => 'Connected to Milvus; collections listed successfully.',
        'collections' => $collections,
      ];
    }
    catch (Exception $e) {
      return [
        'success' => FALSE,
        'message' => 'Milvus test connection failed: ' . $e->getMessage(),
        'collections' => NULL,
      ];
    }
  }

}
