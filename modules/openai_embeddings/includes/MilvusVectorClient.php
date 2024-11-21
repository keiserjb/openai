<?php

class MilvusVectorClient {

  protected $httpClient;
  protected $config;

  /**
   * Constructor to set configuration.
   *
   * @param array $config
   *   Configuration array.
   */
  public function __construct(array $config) {
    $this->config = $config;
  }

  /**
   * Get the Milvus client.
   *
   * @return \GuzzleHttp\Client
   *   The HTTP client.
   */
  public function getClient(): \GuzzleHttp\Client {
    if (!isset($this->httpClient)) {
      $options = [
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $this->config['token'],
          'Accept' => 'application/json',
        ],
        'base_uri' => $this->config['hostname'],
      ];
      $this->httpClient = new \GuzzleHttp\Client($options);
    }
    return $this->httpClient;
  }

  /**
   * Query the vector database.
   *
   * @param array $parameters
   *   Parameters for the query.
   *
   * @return array
   *   The response data.
   */
  public function query(array $parameters): array {
    if (empty($parameters['collection'])) {
      throw new \Exception('Collection name is required by Milvus');
    }
    if (empty($parameters['vector'])) {
      throw new \Exception('Vector to query is required by Milvus');
    }

    $payload = [
      'vector' => $parameters['vector'],
      'collectionName' => $parameters['collection'],
      'limit' => $parameters['top_k'] ?? 5,
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

    $response = $this->getClient()->post('/v1/vector/search', ['json' => $payload]);
    return json_decode($response->getBody()->getContents(), TRUE);
  }

  /**
   * Insert or update vectors in Milvus.
   *
   * @param array $parameters
   *   Parameters for the upsert operation.
   */
  public function upsert(array $parameters): void {
    if (empty($parameters['collection'])) {
      throw new \Exception('Collection name is required by Milvus');
    }
    if (empty($parameters['vectors'])) {
      throw new \Exception('Vectors are required by Milvus');
    }

    $data = $parameters['vectors'];
    $payload = [
      'collectionName' => $parameters['collection'],
      'data' => $data,
    ];

    $this->getClient()->post('/v1/vector/insert', ['json' => $payload]);
  }

  /**
   * Fetch records from Milvus.
   *
   * @param array $parameters
   *   Parameters for fetching records.
   *
   * @return array
   *   The fetched records.
   */
  public function fetch(array $parameters): array {
    if (empty($parameters['collection'])) {
      throw new \Exception('Collection name is required by Milvus');
    }
    if (empty($parameters['source_ids'])) {
      throw new \Exception('Source IDs to fetch are required by Milvus');
    }

    $payload = [
      'collectionName' => $parameters['collection'],
      'filter' => 'source_id in ["' . implode('", "', $parameters['source_ids']) . '"]',
    ];

    $response = $this->getClient()->post('/v1/vector/get', ['json' => $payload]);
    return json_decode($response->getBody()->getContents(), TRUE);
  }

  /**
   * Delete records in Milvus.
   *
   * @param array $parameters
   *   Parameters for deleting records.
   */
  public function delete(array $parameters): void {
    if (empty($parameters['collection'])) {
      throw new \Exception('Collection name is required by Milvus');
    }
    if (empty($parameters['source_ids'])) {
      throw new \Exception('Source IDs to delete are required by Milvus');
    }

    $payload = [
      'collectionName' => $parameters['collection'],
      'filter' => 'source_id in ["' . implode('", "', $parameters['source_ids']) . '"]',
    ];

    $this->getClient()->post('/v1/vector/delete', ['json' => $payload]);
  }

  /**
   * Fetch statistics for Milvus.
   *
   * @return array
   *   The stats array.
   */
  public function stats(): array {
    $response = $this->getClient()->get('/v1/vector/collections');
    return json_decode($response->getBody()->getContents(), TRUE);
  }

  /**
   * Build stats table.
   *
   * @return array
   *   Render array for the stats table.
   */
  public function buildStatsTable(): array {
    $collections = $this->stats();
    $rows = [];

    foreach ($collections as $collection) {
      $rows[] = [
        'Collection' => $collection['collectionName'],
        'Vectors' => $collection['shardsNum'] ?? 'N/A',
      ];
    }

    return [
      '#type' => 'table',
      '#header' => [
        t('Collection Name'),
        t('Vector Count'),
      ],
      '#rows' => $rows,
    ];
  }
}
