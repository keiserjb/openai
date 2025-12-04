<?php

/**
 * Example adapter for the example provider.
 *
 * This file is part of the example provider shipped inside the `openai`
 * module for reference. It intentionally contains a small, safe, non-network
 * implementation so developers can use it as a template. Do not modify other
 * files when editing this example.
 */
class OpenAiExampleAdapter {
  protected $api_key;
  protected $provider_id = 'example';

  public function __construct($api_key = NULL, $provider_id = 'example') {
    $this->api_key = $api_key;
    $this->provider_id = $provider_id;
  }

  // Model discovery - return associative arrays of model_id => display name.
  public function getModels() {
    return [
      'example-chat-1' => 'Example Chat 1',
      'example-chat-2' => 'Example Chat 2',
      'example-image-1' => 'Example Image 1',
      'example-embed-1' => 'Example Embed 1',
      'example-vision-1' => 'Example Vision 1',
      'example-moderation' => 'Example Moderation',
    ];
  }

  /**
   * Filter models by capability.
   *
   * This is a helper method used by the capability-specific methods below.
   * Real providers should implement logic to detect which models support
   * which capabilities, either via API metadata or pattern matching.
   *
   * @param string $capability
   *   The capability to filter by: 'text', 'vision', 'image', 'embeddings', 'moderation'.
   *
   * @return array
   *   Filtered array of model_id => display name.
   */
  public function getModelsByCapability($capability): array {
    $all_models = $this->getModels();
    $filtered = [];

    foreach ($all_models as $id => $name) {
      $ok = FALSE;

      // Simple pattern matching for the example
      switch ($capability) {
        case 'text':
          $ok = strpos($id, 'chat') !== FALSE;
          break;

        case 'image':
          $ok = strpos($id, 'image') !== FALSE;
          break;

        case 'vision':
          $ok = strpos($id, 'vision') !== FALSE;
          break;

        case 'embeddings':
        case 'embedding':
          $ok = strpos($id, 'embed') !== FALSE;
          break;

        case 'moderation':
          $ok = strpos($id, 'moderation') !== FALSE;
          break;
      }

      if ($ok) {
        $filtered[$id] = $name;
      }
    }

    // Allow site-specific overrides via Backdrop's alter hook system
    // This lets site admins override capability detection in custom modules
    $provider_id = $this->provider_id;
    backdrop_alter('openai_model_capabilities', $filtered, $capability, $provider_id);

    return $filtered;
  }

  public function getChatModels() {
    return $this->getModelsByCapability('text');
  }

  public function getImageModels() {
    return $this->getModelsByCapability('image');
  }

  public function getVisionModels() {
    return $this->getModelsByCapability('vision');
  }

  public function getEmbeddingModels() {
    return $this->getModelsByCapability('embeddings');
  }

  public function getModerationModels() {
    return $this->getModelsByCapability('moderation');
  }

  // Operations - these implementations are intentionally simple and local.
  public function chat($model, array $messages, $temperature = 0.7, $max_tokens = 1024, $stream_response = FALSE) {
    // Build a simple concatenated reply for the example.
    $out = "(example) model={$model}\n";
    foreach ($messages as $m) {
      $role = isset($m['role']) ? $m['role'] : 'user';
      $content = isset($m['content']) ? $m['content'] : '';
      $out .= "[{$role}] {$content}\n";
    }
    return $out;
  }

  public function images($model, $prompt, $options = []) {
    // Return a simulated base64 payload (small placeholder) so the core can
    // exercise media saving routines during development without network calls.
    $b64 = base64_encode('example-image-bytes');
    return ['data' => [['b64_json' => $b64]]];
  }

  public function moderation($input, $model = NULL) {
    // Example moderation response: nothing flagged.
    return ['results' => [['flagged' => FALSE, 'categories' => [], 'category_scores' => []]]];
  }

  public function embed($model, $input) {
    // Return a deterministic small vector for tests.
    return [0.001, 0.002, 0.003];
  }

}
