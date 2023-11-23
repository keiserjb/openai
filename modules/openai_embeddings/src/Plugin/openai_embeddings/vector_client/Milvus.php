<?php

declare(strict_types=1);

namespace Drupal\openai_embeddings\Plugin\openai_embeddings\vector_client;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openai_embeddings\VectorClientPluginBase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 * Milvus vector client plugin.
 *
 * @VectorClient(
 *   id = "milvus",
 *   label = "Milvus",
 *   description = "Client implementation to connect and use the Milvus API.",
 * )
 */
class Milvus extends VectorClientPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'hostname' => '',
      'token' => '',
    ];
  }

  /**
   * Get the Milvus client.
   *
   * @return \GuzzleHttp\Client
   *   The http client.
   */
  public function getClient(): Client {
    if (!isset($this->httpClient)) {
      $options = [
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $this->getConfiguration()['token'],
          'Accept' => 'application/json',
        ],
        'base_uri' => $this->getConfiguration()['hostname'],
      ];
      $this->httpClient = $this->http_client_factory->fromOptions($options);
    }
    return $this->httpClient;
  }

  /**
   * Submits a query to the API service.
   *
   * @param array $vector
   *   An array of floats. The size must match the vector size in Milvus.
   * @param int $top_k
   *   How many matches should be returned.
   * @param string $collection
   *   The collection to use, if any.
   * @param array $outputFields
   *   Output fields.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The API response.
   */
  public function query(array $vector, int $top_k = 5, string $collection = '', $outputFields = []): ResponseInterface {
    if (empty($collection)) {
      throw new \Exception('Collection name is required by Milvus');
    }

    $payload = [
      'vector' => $vector,
      'collectionName' => $collection,
      'limit' => $top_k,
    ];
    if ($outputFields) {
      $payload['outputFields'] = $outputFields;
    }

    return $this->getClient()->post('/v1/vector/search', [
      'json' => $payload,
    ]);
  }

  /**
   * Upserts an array of vectors to Milvus.
   *
   * @param array $vectors
   *   An array of vector objects.
   * @param string $collection
   *   The collection to use.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The API response.
   */
  public function upsert(array $vectors, string $collection = ''): ResponseInterface {
    if (empty($collection)) {
      throw new \Exception('Collection name is required by Milvus');
    }

    // Create the collection if not yet existing.
    if (!in_array($collection, $this->listCollections())) {
      $this->getClient()->post('/v1/vector/collections/create', [
        'json' => [
          'collectionName' => $collection,
          'dimension' => 1536,
        ],
        'debug' => TRUE,
      ]);

      // If Milvus fails to create the collection, eg due to invalid
      // characters, it does not throw an error.
      if (!in_array($collection, $this->listCollections())) {
        throw new \Exception('Failed to create collection. Please try a different name without any special characters.');
      }
    }

    // Rearrange data coming from EmbeddingQueueWorker::processItem()
    // to match Milvus expectations.
    $data = $vectors['metadata'];
    $data['vector'] = $vectors['values'];
    $data['source_id'] = $vectors['id'];

    // Delete the existing one if any.
    try {
      $this->delete([$data['source_id']], $collection);
    }
    catch (\Exception $exception) {
      // Do nothing, if it does not exist, carry on and insert.
    }

    $payload = [
      'collectionName' => $collection,
      'data' => $data,
    ];

    return $this->getClient()->post('/v1/vector/insert', [
      'json' => $payload,
    ]);
  }

  /**
   * Look up and returns vectors, by Source ID, from a single collection.
   *
   * @param array $source_ids
   *   One or more IDs to fetch.
   * @param string $collection
   *   The namespace to search in, if applicable.
   * @param array $outputFields
   *   An array of fields to return.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response object.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function fetch(array $source_ids, string $collection = '', array $outputFields = []): ResponseInterface {

    // Milvus uses its own ID, we store the Drupal ID in 'source_id'.
    $payload = [
      'filter' => 'source_id in ["' . implode('", "', $source_ids) . '"]',
    ];
    if (!empty($collection)) {
      $payload['collectionName'] = $collection;
    }
    if (!empty($outputFields)) {
      $payload['outputFields'] = $outputFields;
    }

    return $this->getClient()->query('/v1/vector/get', [
      'json' => $payload,
    ]);
  }

  /**
   * Get Milvus IDs by Source IDs.
   *
   * @param array $source_ids
   *   One or more IDs to fetch.
   * @param string $collection
   *   The namespace to search in, if applicable.
   *
   * @return array
   *   An array of found Milvus IDs.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function fetchMilvusIdsFromSourceIds(array $source_ids, string $collection = '') {
    $id_list = [];
    $response = $this->fetch($source_ids, $collection, ['id'])->getBody();
    if (isset($response['data']) && !empty($response['data'])) {
      foreach ($response['data'] as $result) {
        $id_list[] = $result['id'];
      }
    }
    return $id_list;
  }

  /**
   * Delete records in Milvus.
   *
   * @param array $source_ids
   *   One or more Source IDs to delete.
   * @param string $collection
   *   The collection to delete vectors from.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response object.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function delete(array $source_ids = [], string $collection = ''): ResponseInterface {
    if (empty($collection)) {
      throw new \Exception('Collection name is required by Milvus');
    }

    $ids = $this->fetchMilvusIdsFromSourceIds($source_ids, $collection);
    if (!$ids) {
      throw new \Exception('No Milvus IDs found for the given Source IDs');
    }

    $payload = [];
    if (!empty($collection)) {
      $payload['collectionName'] = $collection;
    }
    if (!empty($ids)) {
      $payload['ids'] = $ids;
    }

    return $this->getClient()->post('/v1/vector/delete', [
      'json' => $payload,
    ]);
  }

  /**
   * Drops a complete collection in Milvus.
   *
   * @param string $collection
   *   The collection to delete vectors from.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response object.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function dropCollection(string $collection = ''): ResponseInterface {
    if (empty($collection)) {
      throw new \Exception('Collection name is required by Milvus');
    }

    return $this->getClient()->post('/v1/vector/collections/drop', [
      'json' => [
        'collectionName' => $collection,
      ],
    ]);
  }

  /**
   * Lists collections.
   *
   * @return array
   *   The response from the API.
   */
  public function listCollections(): array {
    $body = $this->getClient()->get('/v1/vector/collections')->getBody();
    return Json::decode($body)['data'];
  }

  /**
   * Returns statistics about the index's contents.
   */
  public function stats(): array {
    return $this->buildStatsTable();
  }

  /**
   * Build a table with statistics specific to Milvus.
   *
   * @return array
   *   The stats table render array.
   */
  public function buildStatsTable(): array {
    $rows = [];

    $header = [
      ['data' => $this->t('Collections')],
      ['data' => $this->t('Shard (Vector) Count')],
      ['data' => $this->t('Dynamic field storage')],
      ['data' => $this->t('Fields')],
    ];

    try {
      foreach ($this->listCollections() as $collection) {
        $query = UrlHelper::buildQuery([
          'collectionName' => $collection,
        ]);
        $body = $this->getClient()->get('/v1/vector/collections/describe?' . $query)->getBody();
        $data = Json::decode($body)['data'];
        $fields = [];
        foreach ($data['fields'] as $field) {
          $fields[] = $field['name'] . ' (' . $field['type'] . ')';
        }

        $rows[] = [
          $data['collectionName'],
          $data['shardsNum'],
          $data['enableDynamicField'] ? $this->t('Enabled') : $this->t('Disabled'),
          implode(', ', $fields),
        ];
      }
    }
    catch (RequestException | \Exception $e) {
      $this->logger->error('An exception occurred when trying to view index stats. It is likely either configuration is missing or a network error occurred.');
    }

    $build['stats'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No statistics are available.'),
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Token'),
      '#default_value' => $this->getConfiguration()['token'],
      '#description' => $this->t('The API key is required to make calls to Milvus for vector searching.'),
    ];

    $form['hostname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hostname'),
      '#default_value' => $this->getConfiguration()['hostname'],
      '#description' => $this->t('The hostname or base URI where your Milvus instance is located. Include the port if different from standard 443.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
    try {
      $this->listCollections();
    }
    catch (\Exception $exception) {
      $form_state->setErrorByName('hostname', $exception->getMessage());
    }
  }

}
