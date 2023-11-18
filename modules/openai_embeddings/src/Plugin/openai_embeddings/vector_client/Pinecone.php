<?php

declare(strict_types=1);

namespace Drupal\openai_embeddings\Plugin\openai_embeddings\vector_client;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Client;
use Drupal\openai_embeddings\VectorClientPluginBase;
use GuzzleHttp\Exception\RequestException;

/**
 * @VectorClient(
 *   id = "pinecone",
 *   label = "Pinecone",
 *   description = "Client implementation to connect and use the Pinecone API.",
 * )
 */
class Pinecone extends VectorClientPluginBase {

  /**
   * Get the pinecone client.
   *
   * @return Client
   *   The http client.
   */
  public function getClient(): Client {
    if (!isset($this->httpClient)) {
      $options = [
        'headers' => [
          'Content-Type' => 'application/json',
          'API-Key' => $this->getConfiguration()['api_key'],
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
   *   An array of floats. The size must match the vector size in Pinecone.
   * @param int $top_k
   *   How many matches should be returned.
   * @param string $namespace
   *   The namespace to use, if any.
   * @param bool $include_metadata
   *   Includes metadata for the records returned.
   * @param bool $include_values
   *   Includes the values for the records returned. Not usually recommended.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The API response.
   */
  public function query(array $vector, int $top_k = 5, bool $include_metadata = FALSE, bool $include_values = FALSE, array $filters = [], string $namespace = '') {
    $payload = [
      'vector' => $vector,
      'topK' => $top_k,
      'includeMetadata' => $include_metadata,
      'includeValues' => $include_values,
      'namespace' => $namespace,
    ];

    if (!empty($namespace)) {
      $payload['namespace'] = $namespace;
    }

    if (!empty($filters)) {
      $payload['filter'] = $filters;
    }

    return $this->getClient()->post(
      '/query',
      [
        'json' => $payload
      ]
    );
  }

  /**
   * Upserts an array of vectors to Pinecone.
   *
   * @param array $vectors
   *   An array of vector objects.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The API response.
   */
  public function upsert(array $vectors, string $namespace = '') {
    $payload = [
      'vectors' => $vectors,
    ];

    if (!empty($namespace)) {
      $payload['namespace'] = $namespace;
    }

    return $this->getClient()->post(
      '/vectors/upsert',
      [
        'json' => $payload,
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'hostname' => '',
      'api_key' => '',
      'disable_namespace' => 0,
    ];
  }

  /**
   * Look up and returns vectors, by ID, from a single namespace.
   *
   * @param array $ids
   *   One or more IDs to fetch.
   * @param string $namespace
   *   The namespace to search in, if applicable.
   *
   * @return \Psr\Http\Message\ResponseInterface
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function fetch(array $ids, string $namespace = '') {
    $payload = [
      'ids' => $ids,
    ];

    if (!empty($namespace)) {
      $payload['namespace'] = $namespace;
    }

    return $this->getClient()->get(
      '/vectors/fetch',
      [
        'query' => $payload,
      ]
    );
  }

  /**
   * Delete records in Pinecone.
   *
   * @param array $ids
   *   One or more IDs to delete.
   * @param bool $deleteAll
   *   This indicates that all vectors in the index namespace
   *   should be deleted. Use with caution.
   * @param string $namespace
   *   The namespace to delete vectors from, if applicable.
   * @param array $filter
   *   If specified, the metadata filter here will be used to select
   *   the vectors to delete. This is mutually exclusive with
   *   specifying ids to delete in the ids param or using $deleteAll.
   *
   * @return \Psr\Http\Message\ResponseInterface
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function delete(array $ids = [], bool $deleteAll = FALSE, string $namespace = '', array $filter = []) {
    $payload = [];

    // If filter is provided, deleteAll can not be true.
    // If there are no filters, pass what the developer passed.
    if (empty($filter)) {
      $payload['deleteAll'] = $deleteAll;
    }

    if (!empty($namespace)) {
      $payload['namespace'] = $namespace;
    }

    if (!empty($ids)) {
      $payload['ids'] = $ids;
    }

    if (!empty($filter)) {
      $payload['filter'] = $filter;
    }

    return $this->getClient()->post(
      '/vectors/delete',
      [
        'json' => $payload
      ]
    );
  }

  /**
   * Returns statistics about the index's contents.
   */
  public function stats(): array {
    return $this->buildStatsTable();
  }

  /**
   * Build a table with statistics specific to Pinecone.
   *
   * @return array
   *   The stats table render array.
   */
  public function buildStatsTable(): array {
    $rows = [];

    $header = [
      [
        'data' => $this->t('Namespaces'),
      ],
      [
        'data' => $this->t('Vector Count'),
      ],
    ];

    try {
      $stats = $this->getClient()->post(
        '/describe_index_stats',
      );
      $response = Json::decode($stats->getBody()->getContents());

      foreach ($response['namespaces'] as $key => $namespace) {
        if (!mb_strlen($key)) {
          $label = $this->t('No namespace entered');
        }
        else {
          $label = $key;
        }

        $rows[] = [
          $label,
          $namespace['vectorCount'],
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
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $this->getConfiguration()['api_key'],
      '#description' => $this->t('The API key is required to make calls to Pinecone for vector searching.'),
    ];

    $form['hostname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hostname'),
      '#default_value' => $this->getConfiguration()['hostname'],
      '#description' => $this->t('The hostname or base URI where your Pinecone instance is located.'),
    ];

    $form['disable_namespace'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable namespace'),
      '#default_value' => $this->getConfiguration()['disable_namespace'],
      '#description' => $this->t('The starter plan does not support namespaces. This means that all items get indexed together by disabling this; however, it allows you to at least demo the features.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement validateConfigurationForm() method.
  }
}
