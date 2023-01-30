<?php

namespace Drupal\openai_dblog\Controller;

use Drupal\dblog\Controller\DbLogController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Overrides the dblog detail page to provide OpenAI powered answers.
 */
class OpenAIDbLogController extends DbLogController {

  /**
   * The OpenAI client.
   *
   * @var \OpenAI\Client
   */
  protected $client;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->client = $container->get('openai.client');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function eventDetails($event_id) {
    $build = parent::eventDetails($event_id);
    $rows = $build['dblog_table']['#rows'];

    // @todo Only fire this for warnings to critical level messages.
    try {
      $message = trim(strip_tags($rows[5][1]->render()));

      $response = $this->client->completions()->create(
        [
          'model' => 'text-davinci-003',
          'prompt' => 'What does this error mean? The error is: ' . $message,
          'temperature' => 0.4,
          'max_tokens' => 256,
        ],
      );

      $result = $response->toArray();
    }
    catch (\Exception $e) {
      // @todo Catch and log error
    }

    $rows[] = [
      [
        'data' => $this->t('Possible solution'),
        'header' => TRUE,
      ],
      [
        'data' => [
          '#markup' => $result["choices"][0]["text"] ?? 'No solutions were found.',
        ],
      ],
    ];

    $build['dblog_table']['#rows'] = $rows;
    return $build;
  }

}
