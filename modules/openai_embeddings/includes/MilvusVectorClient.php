<?php

use GuzzleHttp\Client as GuzzleClient;

/**
 * Milvus vector client class.
 */
class MilvusVectorClient extends VectorClientBase {

  const API_VERSION = '/v1/vector';

  /**
   * Get the Milvus client.
   *
   * @return \GuzzleHttp\Client
   *   The HTTP client.
   */
  protected function getMilvusClient(): GuzzleClient {
    try {
      // Resolve the token and hostname from configuration.
      $token = $this->resolveConfigValue('milvus_token', 'Milvus token', TRUE);
      $hostname = $this->resolveConfigValue('milvus_hostname', 'Milvus hostname');

      // Validate hostname format.
      if (!filter_var($hostname, FILTER_VALIDATE_URL)) {
        throw new \Exception("Invalid hostname format: $hostname");
      }

      // Trim and format the hostname.
      $hostname = rtrim($hostname, '/');

      // Log resolved values for debugging.
      watchdog('openai_embeddings', "Milvus hostname resolved: @hostname", ['@hostname' => $hostname], WATCHDOG_DEBUG);

      // Return a configured HTTP client.
      return $this->getHttpClient([
        'headers' => [
          'Authorization' => "Bearer $token",
          'Content-Type' => 'application/json',
        ],
        'base_uri' => $hostname . self::API_VERSION,
      ]);
    } catch (\Exception $e) {
      $this->handleError('client initialization', $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query(array $parameters): array {
    $client = $this->getMilvusClient();

    $payload = [
      'vector' => $parameters['vector'],
      'collectionName' => $parameters['collection'],
      'limit' => $parameters['top_k'] ?? self::DEFAULT_TOP_K,
    ];

    if (!empty($parameters['outputFields'])) {
      $payload['outputFields'] = $parameters['outputFields'];
    }

    if (!empty($parameters['filter'])) {
      $filters = [];
      foreach ($parameters['filter'] as $key => $value) {
        $filters[] = $key . ' in [\'' . (is_array($value) ? implode('\', \'', $value) : $value) . '\']';
      }
      $payload['filter'] = implode(' AND ', $filters);
    }

    $this->logPayload('query', $payload);

    try {
      $response = $client->post('/search', ['json' => $payload]);
      $response_data = json_decode($response->getBody()->getContents(), TRUE);

      $this->logResponse('query', $response_data);

      return $response_data;
    } catch (\Exception $e) {
      $this->handleError('query', $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function upsert(array $parameters): void {
    $client = $this->getMilvusClient();

    if (empty($parameters['collection']) || empty($parameters['vectors'])) {
      throw new \Exception('Both collection name and vectors are required for upsert.');
    }

    $payload = [
      'collectionName' => $parameters['collection'],
      'data' => $parameters['vectors'],
    ];

    $this->logPayload('upsert', $payload);

    try {
      $client->post('/insert', ['json' => $payload]);
    } catch (\Exception $e) {
      $this->handleError('upsert', $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $parameters): void {
    $client = $this->getMilvusClient();

    if (empty($parameters['collection']) || empty($parameters['source_ids'])) {
      throw new \Exception('Both collection name and source IDs are required for deletion.');
    }

    $payload = [
      'collectionName' => $parameters['collection'],
      'filter' => 'source_id in ["' . implode('", "', $parameters['source_ids']) . '"]',
    ];

    $this->logPayload('delete', $payload);

    try {
      $client->post('/delete', ['json' => $payload]);
    } catch (\Exception $e) {
      $this->handleError('delete', $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function stats(): array {
    $client = $this->getMilvusClient();

    try {
      $response = $client->get('/collections');
      $stats = json_decode($response->getBody()->getContents(), TRUE);

      $this->logResponse('stats', $stats);

      $rows = [];
      foreach ($stats as $collection) {
        $rows[] = [
          'Collection' => $collection['collectionName'],
          'Vectors' => $collection['shardsNum'] ?? 'N/A',
        ];
      }

      return $rows;
    } catch (\Exception $e) {
      $this->handleError('stats', $e);
      return [];
    }
  }
}
