<?php

/**
 * Queue worker for OpenAI Embeddings in Backdrop CMS.
 */
class EmbeddingQueueWorker {

  /**
   * Process a single queue item.
   *
   * @param array $data
   *   The data for the queue item.
   */
  public function processItem($data) {
    try {
      // Load the entity.
      $entity = node_load($data['entity_id']);
      if (!$entity) {
        throw new Exception("Could not load entity with ID {$data['entity_id']}.");
      }

      // Get configuration.
      $config = config_get('openai_embeddings.settings');
      $stopwords = array_map('trim', explode(',', $config['stopwords'] ?? ''));
      $model = $config['model'] ?? 'text-embedding-ada-002';
      $plugin_id = $config['vector_client_plugin'] ?? NULL;

      // Retrieve the API key from the Key module.
      $openai_config = config('openai.settings');
      $apiKey = key_get_key_value($openai_config->get('api_key'));
      if (!$apiKey) {
        throw new Exception('OpenAI API key is not configured or could not be retrieved.');
      }

      // Initialize OpenAIApi instance.
      $openai_api = new OpenAIApi($apiKey);

      if (!$plugin_id) {
        throw new Exception('Vector client plugin ID is not configured.');
      }

      // Skip entity if its bundle is not allowed.
      $allowed_bundles = $config['content_types'] ?? [];
      if (!in_array($entity->type, $allowed_bundles)) {
        watchdog('openai_embeddings', 'Skipping entity ID: @id because its bundle (@bundle) is not allowed.', [
          '@id' => $entity->nid,
          '@bundle' => $entity->type,
        ], WATCHDOG_INFO);
        return;
      }

      // Load the vector client.
      $vector_client = openai_embeddings_get_vector_client($plugin_id);

      // Supported field types.
      $supported_field_types = ['string', 'text', 'text_long', 'text_with_summary', 'text_textarea_with_summary'];

      // Retrieve field definitions.
      $fields = field_info_instances('node', $entity->type);

      foreach ($fields as $field_name => $field_info) {
        $field_type = $field_info['type'] ?? ($field_info['widget']['type'] ?? 'undefined');

        // Check if the field type is supported.
        if (!in_array($field_type, $supported_field_types)) {
          watchdog('openai_embeddings', 'Skipping unsupported field: @field_name, Type: @field_type', [
            '@field_name' => $field_name,
            '@field_type' => $field_type,
          ], WATCHDOG_INFO);
          continue;
        }

        // Retrieve field values.
        $field_items = field_get_items('node', $entity, $field_name);
        if (empty($field_items)) {
          continue; // Do not log empty fields to reduce noise.
        }

        foreach ($field_items as $delta => $item) {
          if (empty($item['value'])) {
            continue;
          }

          // Prepare text and remove stopwords.
          $text = openai_embeddings_prepare_text($item['value'], 8000);
          foreach ($stopwords as $word) {
            $text = $this->removeStopWord($word, $text);
          }

          // Generate embedding using OpenAIApi and model.
          $embedding = $openai_api->embedding($text, $model);
          if (empty($embedding)) {
            watchdog('openai_embeddings', 'Failed to generate embedding for entity ID: @id, field: @field.', [
              '@id' => $entity->nid,
              '@field' => $field_name,
            ], WATCHDOG_WARNING);
            continue;
          }

          // Dynamically determine the namespace based on entity type.
          $collection = $data['entity_type']; // Use the entity type directly.

          // Prepare vector metadata.
          $unique_id = $this->generateUniqueId($entity, $field_name, $delta);
          $vectors = [
            'id' => $unique_id,
            'values' => $embedding,
            'metadata' => [
              'entity_id' => $entity->nid,
              'entity_type' => $data['entity_type'],
              'bundle' => $entity->type,
              'field_name' => $field_name,
              'field_delta' => $delta,
            ],
          ];

          // Upsert into vector database.
          $vector_client->upsert([
            'vectors' => [$vectors],
            'collection' => $collection,
          ]);

          // Update the local database.
          db_merge('openai_embeddings')
            ->key([
              'entity_id' => $entity->nid,
              'entity_type' => $data['entity_type'],
              'bundle' => $entity->type,
              'field_name' => $field_name,
              'field_delta' => $delta,
            ])
            ->fields([
              'embedding' => json_encode(['data' => $embedding]),
              'data' => json_encode(['usage' => []]), // Update with actual usage data if needed.
            ])
            ->execute();
        }
      }
    } catch (Exception $e) {
      watchdog('openai_embeddings', 'Error processing queue item: @message', [
        '@message' => $e->getMessage(),
      ], WATCHDOG_ERROR);
    }
  }

  /**
   * Generates a unique ID for the record in the vector database.
   *
   * @param object $entity
   *   The entity object.
   * @param string $field_name
   *   The field name.
   * @param int $delta
   *   The field delta.
   *
   * @return string
   *   The unique ID.
   */
  protected function generateUniqueId($entity, $field_name, $delta) {
    return 'entity:' . $entity->nid . ':node:' . $entity->type . ':' . $field_name . ':' . $delta;
  }

  /**
   * Remove a stop word from text.
   *
   * @param string $word
   *   The stop word to remove.
   * @param string $text
   *   The text to process.
   *
   * @return string
   *   The processed text.
   */
  protected function removeStopWord($word, $text) {
    return preg_replace("/\b$word\b/i", '', trim($text));
  }
}
