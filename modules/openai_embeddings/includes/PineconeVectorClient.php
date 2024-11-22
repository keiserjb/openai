<?php

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 * Pinecone vector client class.
 */
class PineconeVectorClient extends VectorClientBase {

  /**
   * Initialize a Pinecone-specific HTTP client.
   *
   * @return \GuzzleHttp\Client
   *   The configured HTTP client for Pinecone.
   */
  protected function getPineconeClient(): \GuzzleHttp\Client {
    // Retrieve Pinecone API key and hostname from configuration.
    $api_key = $this->getEmbeddingConfig('pinecone_api_key');
    $hostname = $this->getEmbeddingConfig('pinecone_hostname');

    // If the Key module is used, resolve the values.
    if (is_array($api_key)) {
      $api_key = key_get_key_value($api_key);
    }
    if (is_array($hostname)) {
      $hostname = key_get_key_value($hostname);
    }

    // Validate and sanitize the values.
    if (empty($api_key) || !is_string($api_key)) {
      throw new \Exception('Invalid or missing Pinecone API key. Please check your configuration.');
    }
    if (empty($hostname) || !is_string($hostname)) {
      throw new \Exception('Invalid or missing Pinecone hostname. Please check your configuration.');
    }

    // Trim and sanitize the hostname and API key.
    $api_key = trim($api_key);
    $hostname = rtrim(trim($hostname), '/');

    // Return a configured HTTP client.
    return $this->getHttpClient([
      'headers' => [
        'API-Key' => $api_key,
      ],
      'base_uri' => $hostname,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function query(array $parameters) {
    $client = $this->getPineconeClient();

    $payload = [
        'vector' => $parameters['vector'],
        'topK' => $parameters['top_k'] ?? 5,
        'includeMetadata' => true, // Ensure metadata is requested
    ];

    if (!empty($parameters['collection'])) {
        $payload['namespace'] = $parameters['collection'];
    }

    /* watchdog('openai_embeddings', 'Pinecone query payload: @payload', [
        '@payload' => json_encode($payload),
    ], WATCHDOG_DEBUG); */

    try {
        $response = $client->post('/query', ['json' => $payload]);
        $response_data = json_decode($response->getBody()->getContents(), TRUE);

        // Log response for debugging
        /* watchdog('openai_embeddings', 'Pinecone query response: @response', [
            '@response' => print_r($response_data, TRUE),
        ], WATCHDOG_DEBUG); */

        return $response_data;
    } catch (\Exception $e) {
        watchdog('openai_embeddings', 'Error querying Pinecone: @message', ['@message' => $e->getMessage()], WATCHDOG_ERROR);
        throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function upsert(array $parameters) {
    $client = $this->getPineconeClient();

    $payload = [
        'vectors' => $parameters['vectors'],
    ];

    if (!empty($parameters['collection'])) {
        $payload['namespace'] = $parameters['collection'];
    }

    // Log the payload being sent for upsert.
     watchdog('openai_embeddings', 'Pinecone upsert payload: @payload', [
        '@payload' => json_encode($payload),
    ], WATCHDOG_DEBUG);

    try {
        $response = $client->post('/vectors/upsert', ['json' => $payload]);
        /* watchdog('openai_embeddings', 'Pinecone upsert response: @response', [
            '@response' => $response->getBody()->getContents(),
        ], WATCHDOG_DEBUG); */
        return $response;
    } catch (RequestException $e) {
        watchdog('openai_embeddings', 'Error during Pinecone upsert: @message', ['@message' => $e->getMessage()], WATCHDOG_ERROR);
        throw $e;
    } catch (Exception $e) {
       watchdog('openai_embeddings', 'Unexpected error during Pinecone upsert: @message', ['@message' => $e->getMessage()], WATCHDOG_ERROR);
        throw $e;
    }
}

  /**
 * {@inheritdoc}
 */
public function stats(): array {
  try {
      $client = $this->getPineconeClient();
      $response = $client->post('/describe_index_stats');
      $stats = json_decode($response->getBody()->getContents(), TRUE);

      // Log the full stats response.
      /* watchdog('openai_embeddings', 'Pinecone stats response: @response', [
          '@response' => print_r($stats, TRUE),
      ], WATCHDOG_DEBUG); */

      $rows = [];

      if (!empty($stats['namespaces'])) {
          foreach ($stats['namespaces'] as $namespace => $data) {
              $rows[] = [
                  'Namespace' => $namespace ?: t('No namespace'),
                  'Vector Count' => $data['vectorCount'] ?? 0,
              ];
          }
      } else {
         // watchdog('openai_embeddings', 'No namespaces found in Pinecone stats.', [], WATCHDOG_INFO);
      }

      return $rows;
  } catch (RequestException $e) {
      watchdog('openai_embeddings', 'Error fetching Pinecone stats: @message', [
          '@message' => $e->getMessage(),
      ], WATCHDOG_ERROR);
      return [];
  } catch (Exception $e) {
      watchdog('openai_embeddings', 'Unexpected error in Pinecone stats: @message', [
          '@message' => $e->getMessage(),
      ], WATCHDOG_ERROR);
      return [];
  }
}

  /**
   * Delete records in Pinecone.
   *
   * @param array $parameters
   *   An array with at least key 'source_ids'. The key
   *   'collection' is required if not using the Pinecone free Starter plan.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response object.
   *
   * @throws \Exception
   *   If required parameters are missing or an error occurs.
   */
  public function delete(array $parameters): ResponseInterface {
    // Ensure necessary parameters are provided.
    if (empty($parameters['source_ids']) && empty($parameters['filter'])) {
      throw new \Exception('Either "source_ids" to delete or a "filter" is required for Pinecone deletion.');
    }

    if (!empty($parameters['deleteAll'])) {
      throw new \Exception('"deleteAll" must be handled by the deleteAll() method.');
    }

    // Prepare the payload for deletion.
    $payload = [];
    if (!empty($parameters['source_ids'])) {
      $payload['ids'] = $parameters['source_ids'];
    }
    if (!empty($parameters['collection'])) {
      $payload['namespace'] = $parameters['collection'];
    }
    if (!empty($parameters['filter'])) {
      $payload['filter'] = $parameters['filter'];
    }

    try {
      // Execute the delete request.
      return $this->getClient()->post('/vectors/delete', ['json' => $payload]);
    } catch (RequestException $e) {
      watchdog('openai_embeddings', 'Error during Pinecone delete: @message', ['@message' => $e->getMessage()], WATCHDOG_ERROR);
      throw $e;
    }
  }
  
  /**
   * Delete all records in Pinecone.
   *
   * @param array $parameters
   *   An array with at least the key 'collection'. Full deletion
   *   is not supported in the Pinecone free Starter plan.
   *
   * @throws \Exception
   *   If required parameters are missing or an error occurs.
   */
  public function deleteAll(array $parameters): void {
    // Use the configuration method provided by the VectorClientBase class.
    $disable_namespace = $this->getEmbeddingConfig('disable_namespace');

    // Validate the Pinecone plan and required parameters.
    if (!empty($disable_namespace)) {
      watchdog('openai_embeddings', 'Pinecone free starter plan does not support Delete All.', [], WATCHDOG_WARNING);
      throw new \Exception('Pinecone free starter plan does not support full namespace deletion.');
    }
    if (empty($parameters['collection'])) {
      throw new \Exception('Namespace (collection) is required for deleteAll in Pinecone.');
    }

    // Retrieve Pinecone API key and hostname from configuration.
    $api_key = $this->getEmbeddingConfig('pinecone_api_key');
    $hostname = $this->getEmbeddingConfig('pinecone_hostname');

    // If the Key module is used, resolve the values.
    if (is_array($api_key)) {
      $api_key = key_get_key_value($api_key);
    }
    if (is_array($hostname)) {
      $hostname = key_get_key_value($hostname);
    }

    // Validate the resolved values.
    if (empty($api_key) || !is_string($api_key)) {
      throw new \Exception('Invalid or missing Pinecone API key. Please check your configuration.');
    }
    if (empty($hostname) || !is_string($hostname)) {
      throw new \Exception('Invalid or missing Pinecone hostname. Please check your configuration.');
    }

    // Trim and sanitize the hostname and API key.
    $api_key = trim($api_key);
    $hostname = rtrim(trim($hostname), '/');

    // Prepare the payload for full deletion.
    $payload = [
      'deleteAll' => TRUE,
      'namespace' => $parameters['collection'],
    ];

    try {
      // Create a Guzzle client with the resolved configuration.
      $client = $this->getHttpClient([
        'headers' => [
          'API-Key' => $api_key,
        ],
        'base_uri' => $hostname,
      ]);

      // Execute the deleteAll request.
      $client->post('/vectors/delete', ['json' => $payload]);
    } catch (RequestException $e) {
      watchdog('openai_embeddings', 'Error during Pinecone deleteAll: @message', ['@message' => $e->getMessage()], WATCHDOG_ERROR);
      throw $e;
    } catch (\Exception $e) {
      watchdog('openai_embeddings', 'Unexpected error during Pinecone deleteAll: @message', ['@message' => $e->getMessage()], WATCHDOG_ERROR);
      throw $e;
    }
  }



}

