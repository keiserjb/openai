<?php

namespace Drupal\openai_embeddings\Plugin\QueueWorker;

use Drupal\Core\Annotation\QueueWorker;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use OpenAI\Client;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;

/**
 * Queue worker for OpenAI Embeddings module.
 *
 * @QueueWorker(
 *   id = "embedding_queue",
 *   title = @Translation("Embedding Queue"),
 *   cron = {"time" = 30}
 * )
 */
final class EmbeddingQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The OpenAI client.
   *
   * @var \OpenAI\Client
   */
  protected $client;

  /**
   * The logger factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, Connection $connection, Client $client, LoggerChannelFactoryInterface $logger_channel_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->database = $connection;
    $this->client = $client;
    $this->logger = $logger_channel_factory->get('openai_embeddings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('database'),
      $container->get('openai.client'),
      $container->get('logger.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // @todo Wrap this in a try catch
    $entity = $this->entityTypeManager->getStorage($data['entity_type'])->load($data['entity_id']);
    $fields = $this->entityFieldManager->getFieldDefinitions($data['entity_type'], $data['bundle']);
    $field_types = $this->getFieldTypes();

    foreach ($fields as $field) {
      if (in_array($field->getType(), $field_types)) {
        $field_values = $entity->get($field->getName())->getValue();

        foreach ($field_values as $delta => $data) {
          if (!mb_strlen($data['value'])) {
            continue;
          }

          try {
            $response = $this->client->embeddings()->create([
              'model' => 'text-embedding-ada-002',
              'input' => $data['value'],
            ]);

            $embeddings = $response->toArray();

            $this->database->merge('openai_embeddings')
              ->keys(
                [
                  'entity_id' => $entity->id(),
                  'entity_type' => $entity->getEntityTypeId(),
                  'bundle' => $entity->bundle(),
                  'field_name' => $field->getName(),
                  'field_delta' => $delta,
                ]
              )
              ->fields(
                [
                  'embedding' => json_encode(['data' => $embeddings["data"][0]["embedding"]]) ?? [],
                  'data' => json_encode(['usage' => $embeddings["usage"]]),
                ]
              )
              ->execute();
          }
          catch (\Exception $e) {
            $this->logger->error(
              'An exception occurred while trying to generate embeddings for a :entity_type with the ID of :entity_id on the :field_name field, with a delta of :field_delta. The bundle of this entity is :bundle.',
              [
                ':entity_type' => $entity->getEntityTypeId(),
                ':entity_id' => $entity->id(),
                ':field_name' => $field->getName(),
                ':field_delta' => $delta,
                ':bundle' => $entity->bundle(),
              ]
            );
          }
        }
      }
    }
  }

  /**
   * A list of string/text field types.
   *
   * @return string[]
   */
  protected function getFieldTypes(): array {
    return [
      'string',
      'string_long',
      'text',
      'text_long',
      'text_with_summary'
    ];
  }

}
