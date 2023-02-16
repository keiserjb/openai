<?php

namespace Drupal\openai_embeddings\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\openai_embeddings\Http\PineconeClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for OpenAI Embeddings routes.
 */
class PineconeStats extends ControllerBase {

  /**
   * The openai_embeddings.pinecone_client service.
   *
   * @var \Drupal\openai_embeddings\Http\PineconeClient
   */
  protected $pineconeClient;

  /**
   * The controller constructor.
   *
   * @param \Drupal\openai_embeddings\Http\PineconeClient $pinecone_client
   *   The openai_embeddings.pinecone_client service.
   */
  public function __construct(PineconeClient $pinecone_client) {
    $this->pineconeClient = $pinecone_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('openai_embeddings.pinecone_client')
    );
  }

  /**
   * Builds the response.
   */
  public function index() {
    $stats = $this->pineconeClient->stats();
    $response = json_decode($stats->getBody()->getContents(), JSON_OBJECT_AS_ARRAY);
    $rows = [];

    $header = [
      [
        'data' => $this->t('Namespaces'),
      ],
      [
        'data' => $this->t('Vector Count'),
      ],
    ];

    foreach ($response['namespaces'] as $key => $namespace) {
      if (!mb_strlen($key)) {
        $label = $this->t('No namespace entered');
      } else {
        $label = $key;
      }

      $rows[] = [
        $label,
        $namespace['vectorCount'],
      ];
    }

    $build['stats'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No statistics are available.'),
    ];

    return $build;
  }

}
