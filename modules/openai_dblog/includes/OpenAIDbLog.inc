<?php

class OpenAIDbLog {

  protected $api;

  public function __construct() {
    $config = config_get('openai.settings');

    // Get the key value using the key module.
    $api_key = key_get_key_value($config['api_key']);

    if (empty($api_key)) {
      throw new Exception(t('The OpenAI API key is not set or is invalid. Please configure it in the OpenAI settings.'));
    }

    $this->api = new OpenAIApi($api_key);
  }

  public function getOpenAIExplanation($dblog) {
    // Generate a message to send to OpenAI API
    $message = truncate_utf8(trim(strip_tags(dblog_format_message($dblog))), 256, TRUE);

    // Fetch the model from the settings
    $config = config_get('openai_dblog.settings');
    $model = $config['model'];

    // Call OpenAI API
    try {
      if (str_contains($model, 'gpt')) {
        $messages = [
          [
            'role' => 'system',
            'content' => 'You are a PHP, Backdrop CMS, and Drupal expert programmer. Please return all answers without using first, second, or third person voice.',
          ],
          [
            'role' => 'user',
            'content' => 'What does this error mean on my Backdrop site and how can I fix it? The error is: "' . $message . '"',
          ],
        ];
        $result = $this->api->chat($model, $messages, 0.4, 3900);
      } else {
        $prompt = 'What does this error mean on my Backdrop site and how can I fix it? The error is: "' . $message . '"';
        $result = $this->api->completions($model, $prompt, 0.4, 2048);
      }
      watchdog('openai_dblog', 'OpenAI API response: ' . print_r($result, TRUE));
      return nl2br($result);
    } catch (Exception $e) {
      watchdog('openai_dblog', 'Error when trying to obtain a response from OpenAI: ' . $e->getMessage());
      return t('No possible explanations were found, or the API service is not responding.');
    }
  }

  protected function generateHash($message) {
    return hash('sha256', $message);
  }

  protected function hashSearch($hash) {
    return db_query('SELECT explanation FROM {openai_dblog} WHERE hash = :hash', [':hash' => $hash])->fetchAssoc();
  }

  protected function insertExplanation($hash, $message, $explanation) {
    db_insert('openai_dblog')
      ->fields([
        'hash' => $hash,
        'message' => $message,
        'explanation' => strip_tags(trim($explanation)),
      ])
      ->execute();
  }

  public function processExplanation($dblog) {
    $message = dblog_format_message($dblog);
    $hash = $this->generateHash($message);

    $existing_explanation = $this->hashSearch($hash);
    if ($existing_explanation) {
      return $existing_explanation['explanation'];
    }

    $explanation = $this->getOpenAIExplanation($dblog);
    $this->insertExplanation($hash, $message, $explanation);

    return $explanation;
  }
}

