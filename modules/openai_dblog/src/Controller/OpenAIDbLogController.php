<?php

namespace Drupal\openai_dblog\Controller;

use Drupal\dblog\Controller\DbLogController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Overrides the log details page to provide OpenAI powered explanations.
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
    $severity = trim(strip_tags($rows[6][1]->render()));
    $config = $this->config('openai_dblog.settings');
    $levels = $config->get('levels');

    if (!array_key_exists($severity, $levels)) {
      return $build;
    }

    $result = [];

    try {
      $message = trim(strip_tags($rows[5][1]->render()));

      $response = $this->client->completions()->create(
        [
          'model' => 'text-davinci-003',
          'prompt' => 'What does this error mean? The error is: ' . $message . ' How can I fix this? Explain like I am 5.',
          'temperature' => 0.4,
          'max_tokens' => 256,
        ],
      );

      $result = $response->toArray();
    }
    catch (\Exception $e) {
      // @todo Catch and log error
    }

    if (!empty($result)) {
      $rows[] = [
        [
          'data' => $this->t('Explanation (powered by <a href="@link">OpenAI</a>)', ['@link' => 'https://openai.com']),
          'header' => TRUE,
        ],
        [
          'data' => [
            '#markup' => $result["choices"][0]["text"] ?? 'No possible explanations were found, or the API service is not responding.',
          ],
        ],
      ];
    }

    $build['dblog_table']['#rows'] = $rows;
    return $build;
  }

}
