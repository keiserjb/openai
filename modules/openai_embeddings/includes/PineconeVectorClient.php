<?php

use GuzzleHttp\Client as GuzzleClient;

/**
 * Pinecone vector client class.
 */
class PineconeVectorClient extends VectorClientBase {

  const DEFAULT_TOP_K = 5;

  /**
   * Resolve a configuration value from settings.
   *
   * @param string $key
   *   The key in the configuration array.
   * @param string $description
   *   A description of the setting for debugging.
   * @param bool $resolve_key
   *   Whether to use key_get_key_value() to resolve the value (for keys).
   *
   * @return string
   *   The resolved and validated value.
   *
   * @throws \Exception
   *   If the configuration is invalid or missing.
   */
  protected function resolveConfigValue(string $key, string $description, bool $resolve_key = FALSE): string {
    // Fetch the configuration value.
    $value = config_get('openai_embeddings.settings')[$key] ?? NULL;

    // Resolve using Key module if necessary.
    if ($resolve_key && $value) {
      $value = key_get_key_value($value);
    }

    // Validate the value.
    if (empty($value) || !is_string($value)) {
      watchdog('openai_embeddings', "Invalid or missing $description for key $key. Value: @value", [
        '@value' => $value ?? 'NULL',
      ], WATCHDOG_ERROR);
      throw new \Exception("Invalid or missing $description.");
    }

    return trim($value);
  }

  /**
   * Initialize a Pinecone-specific HTTP client.
   *
   * @return \GuzzleHttp\Client
   *   The configured HTTP client for Pinecone.
   */
  protected function getPineconeClient(): GuzzleClient {
    try {
      // Resolve Pinecone API key and hostname.
      $api_key = $this->resolveConfigValue('pinecone_api_key', 'Pinecone API key', TRUE);
      $hostname = $this->resolveConfigValue('pinecone_hostname', 'Pinecone hostname', TRUE);

      // Ensure hostname format.
      $hostname = rtrim($hostname, '/');
      if (!filter_var($hostname, FILTER_VALIDATE_URL)) {
        throw new \Exception("Invalid hostname format: $hostname");
      }

      // Log resolved values for debugging.
      watchdog('openai_embeddings', "Pinecone hostname resolved: @hostname", ['@hostname' => $hostname], WATCHDOG_DEBUG);

      // Return configured client.
      return $this->getHttpClient([
        'headers' => ['API-Key' => $api_key],
        'base_uri' => $hostname,
      ]);
    } catch (\Exception $e) {
      watchdog('openai_embeddings', 'Error resolving Pinecone client: @message', [
        '@message' => $e->getMessage(),
      ], WATCHDOG_ERROR);
      throw $e;
    }
  }

  /**
   * Log the payload for debugging purposes.
   *
   * @param string $action
   *   The action being logged (e.g., 'query', 'upsert').
   * @param array $payload
   *   The payload data.
   */
  protected function logPayload(string $action, array $payload): void {
    watchdog('openai_embeddings', "Pinecone $action payload: @payload", [
      '@payload' => json_encode($payload, JSON_PRETTY_PRINT),
    ], WATCHDOG_DEBUG);
  }

  /**
   * Log the response for debugging purposes.
   *
   * @param string $action
   *   The action being logged (e.g., 'query', 'upsert').
   * @param array $response
   *   The response data.
   */
  protected function logResponse(string $operation, $response): void {
    watchdog('openai_embeddings', "Pinecone $operation response: @response", [
      '@response' => print_r($response, TRUE),
    ], WATCHDOG_DEBUG);
  }

  /**
   * Handle and log errors for debugging purposes.
   *
   * @param string $action
   *   The action that triggered the error.
   * @param \Exception $e
   *   The exception to handle.
   *
   * @throws \Exception
   *   The rethrown exception after logging.
   */
  protected function handleError(string $action, \Exception $e): void {
    watchdog('openai_embeddings', "Error during Pinecone $action: @message", [
      '@message' => $e->getMessage(),
    ], WATCHDOG_ERROR);
    throw $e;
  }

  /**
   * Perform a query operation.
   *
   * @param array $parameters
   *   The query parameters.
   *
   * @return array
   *   The query results.
   */
  public function query(array $parameters) {
    $client = $this->getPineconeClient();

    $payload = [
      'vector' => $parameters['vector'],
      'topK' => $parameters['top_k'] ?? self::DEFAULT_TOP_K,
      'includeMetadata' => true,
    ];

    if (!empty($parameters['collection'])) {
      $payload['namespace'] = $parameters['collection'];
    }

    $this->logPayload('query', $payload);

    try {
      $response = $client->post('/query', ['json' => $payload]);
      $response_data = json_decode($response->getBody()->getContents(), TRUE);

      $this->logResponse('query', $response_data);

      return $response_data;
    } catch (\Exception $e) {
      $this->handleError('query', $e);
    }
  }

  /**
   * Perform an upsert operation.
   *
   * @param array $parameters
   *   The upsert parameters.
   *
   * @return \Psr\Http\Message\ResponseInterface|null
   *   The response object or NULL if an error occurs.
   */
  public function upsert(array $parameters) {
    $client = $this->getPineconeClient();

    $payload = [
      'vectors' => $parameters['vectors'],
    ];

    if (!empty($parameters['collection'])) {
      $payload['namespace'] = $parameters['collection'];
    }

    $this->logPayload('upsert', $payload);

    try {
      $response = $client->post('/vectors/upsert', ['json' => $payload]);
      $this->logResponse('upsert', json_decode($response->getBody()->getContents(), TRUE));
      return $response;
    } catch (\Exception $e) {
      $this->handleError('upsert', $e);
    }
  }

  /**
   * Fetch stats from Pinecone.
   *
   * @return array
   *   The stats response.
   */
  public function stats(): array {
    try {
      $client = $this->getPineconeClient();
      $response = $client->post('/describe_index_stats');
      $stats = json_decode($response->getBody()->getContents(), TRUE);

      $this->logResponse('stats', $stats);

      $rows = [];

      if (!empty($stats['namespaces'])) {
        foreach ($stats['namespaces'] as $namespace => $data) {
          $rows[] = [
            'Namespace' => $namespace ?: t('No namespace'),
            'Vector Count' => $data['vectorCount'] ?? 0,
          ];
        }
      }

      return $rows;
    } catch (\Exception $e) {
      $this->handleError('stats', $e);
      return [];
    }
  }

  /**
   * Delete records in Pinecone.
   *
   * @param array $parameters
   *   The delete parameters.
   *
   * @return \Psr\Http\Message\ResponseInterface|null
   *   The response object or NULL if an error occurs.
   */
  public function delete(array $parameters): ?ResponseInterface {
    if (empty($parameters['source_ids']) && empty($parameters['filter'])) {
      throw new \Exception('Either "source_ids" or "filter" is required for Pinecone deletion.');
    }

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

    $this->logPayload('delete', $payload);

    try {
      $client = $this->getPineconeClient();
      return $client->post('/vectors/delete', ['json' => $payload]);
    } catch (\Exception $e) {
      $this->handleError('delete', $e);
      return NULL;
    }
  }

  /**
   * Delete all records in Pinecone.
   *
   * @param array $parameters
   *   The delete all parameters.
   */
  public function deleteAll(array $parameters): void {
    $disable_namespace = config_get('openai_embeddings.settings')['pinecone_disable_namespace'] ?? NULL;

    if (!empty($disable_namespace)) {
      watchdog('openai_embeddings', 'Namespace deletion is disabled for the current Pinecone plan.', [], WATCHDOG_WARNING);
      throw new \Exception('Namespace deletion is not allowed for this Pinecone configuration.');
    }

    if (empty($parameters['collection'])) {
      throw new \Exception('A namespace (collection) is required to perform deleteAll in Pinecone.');
    }

    $payload = [
      'deleteAll' => TRUE,
      'namespace' => $parameters['collection'],
    ];

    $this->logPayload('deleteAll', $payload);

    try {
      $client = $this->getPineconeClient();
      $client->post('/vectors/delete', ['json' => $payload]);
    } catch (\Exception $e) {
      $this->handleError('deleteAll', $e);
    }
  }
}
