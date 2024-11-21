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
    $api_key = $this->getEmbeddingConfig('pinecone_api_key');
    $hostname = rtrim($this->getEmbeddingConfig('pinecone_hostname'), '/');

    if (empty($api_key) || empty($hostname)) {
      throw new \Exception('Pinecone API key or hostname is not set. Please check your configuration.');
    }

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
    /* watchdog('openai_embeddings', 'Pinecone upsert payload: @payload', [
        '@payload' => json_encode($payload),
    ], WATCHDOG_DEBUG); */

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
  
}

