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
    // Check if Search API AI is currently handling indexing.
    // The 'search_api_ai_is_indexing' global variable is set by
    // search_api_ai's indexing process.
    global $search_api_ai_is_indexing;

    if (!empty($search_api_ai_is_indexing)) {
      // If Search API AI is indexing, skip openai_embeddings's upsert
      // to avoid duplicate operations.
      // This will prevent openai_embeddings from processing items if search_api_ai
      // is already handling the embedding generation and upsert for the same content.
      watchdog(
        'openai_embeddings',
        'Skipping queue item for @entity_type:@entity_id as Search API AI is actively indexing.',
        [
          '@entity_type' => $data['entity_type'] ?? 'unknown',
          '@entity_id' => $data['entity_id'] ?? 'unknown',
        ],
        WATCHDOG_INFO
      );
      return;
    }

    try {
      // Load the entity based on entity type.
      if ($data['entity_type'] === 'paragraphs_item') {
        $entity = paragraphs_item_load($data['entity_id']);
        if (!$entity) {
          throw new Exception("Could not load paragraph entity with ID {$data['entity_id']}.");
        }
      }
      elseif ($data['entity_type'] === 'taxonomy_term') {
        $entity = taxonomy_term_load($data['entity_id']);
        if (!$entity) {
          throw new Exception("Could not load taxonomy term with ID {$data['entity_id']}.");
        }
      }
      else {
        $entity = node_load($data['entity_id']);
        if (!$entity) {
          throw new Exception("Could not load node with ID {$data['entity_id']}.");
        }
      }

      // Load configuration and API settings.
      $config = config_get('openai_embeddings.settings');
      $stopwords = array_map('trim', explode(',', $config['stopwords'] ?? ''));
      $model = $config['model'] ?? 'text-embedding-ada-002';
      $plugin_id = $config['vector_client_plugin'] ?? NULL;

      if (!$plugin_id) {
        throw new Exception('Vector client plugin ID is not configured.');
      }

      $openai_config = config('openai.settings');
      $apiKey = key_get_key_value($openai_config->get('api_key'));
      if (!$apiKey) {
        throw new Exception('OpenAI API key is not configured or could not be retrieved.');
      }

      $openai_api = new OpenAIApi($apiKey);
      $vector_client = openai_embeddings_get_vector_client($plugin_id);

      // Validate bundle based on entity type.
      if ($data['entity_type'] === 'paragraphs_item') {
        $allowed_bundles = $config['paragraph_bundles'] ?? [];
        if (!in_array($entity->bundle, $allowed_bundles)) {
          watchdog('openai_embeddings', 'Skipping paragraph ID: @id because its bundle (@bundle) is not allowed.', [
            '@id' => $entity->item_id,
            '@bundle' => $entity->bundle,
          ], WATCHDOG_INFO);
          return;
        }
      }
      elseif ($data['entity_type'] === 'taxonomy_term') {
        $allowed_vocabularies = $config['taxonomy_vocabularies'] ?? [];
        if (!in_array($entity->vocabulary, $allowed_vocabularies)) {
          watchdog('openai_embeddings', 'Skipping taxonomy term ID: @id because its vocabulary (@bundle) is not allowed.', [
            '@id' => $entity->tid,
            '@bundle' => $entity->vocabulary,
          ], WATCHDOG_INFO);
          return;
        }

        // Special handling for taxonomy terms.
        $text_fields = [];
        if (!empty($entity->name)) {
          $text_fields[] = $entity->name;
        }
        if (!empty($entity->description)) {
          $text_fields[] = $entity->description;
        }

        foreach ($text_fields as $delta => $text) {
          foreach ($stopwords as $word) {
            $text = $this->removeStopWord($word, $text);
          }

          $embedding = $openai_api->embedding($text, $model);
          if (empty($embedding)) {
            watchdog('openai_embeddings', 'Failed to generate embedding for taxonomy term ID: @id.', [
              '@id' => $entity->tid,
            ], WATCHDOG_WARNING);
            continue;
          }

          $collection = 'taxonomy_term';
          $unique_id = $this->generateUniqueId($entity, 'taxonomy_field', $delta, 'taxonomy_term');

          $vectors = [
            'id' => $unique_id,
            'values' => $embedding,
            'metadata' => [
              'entity_id' => $entity->tid,
              'entity_type' => 'taxonomy_term',
              'bundle' => $entity->vocabulary,
              'field_name' => 'taxonomy_field',
              'field_delta' => $delta,
              'content' => $text,
            ],
          ];

          try {
            $vector_client->upsert([
              'vectors' => [$vectors],
              'collection' => $collection,
            ]);

            db_merge('openai_embeddings')
              ->key([
                'entity_id' => $entity->tid,
                'entity_type' => 'taxonomy_term',
                'bundle' => $entity->vocabulary,
                'field_name' => 'taxonomy_field',
                'field_delta' => $delta,
              ])
              ->fields([
                'embedding' => json_encode(['data' => $embedding]),
                'data' => json_encode(['usage' => []]),
              ])
              ->execute();

            watchdog('openai_embeddings', 'Successfully stored embedding for taxonomy term ID: @id', [
              '@id' => $entity->tid,
            ], WATCHDOG_INFO);
          }
          catch (\Exception $e) {
            watchdog('openai_embeddings', 'Failed to store embedding in vector database for taxonomy term ID: @id - @error', [
              '@id' => $entity->tid,
              '@error' => $e->getMessage()
            ], WATCHDOG_ERROR);

            // Store in database even if vector database fails
            db_merge('openai_embeddings')
              ->key([
                'entity_id' => $entity->tid,
                'entity_type' => 'taxonomy_term',
                'bundle' => $entity->vocabulary,
                'field_name' => 'taxonomy_field',
                'field_delta' => $delta,
              ])
              ->fields([
                'embedding' => json_encode(['data' => $embedding]),
                'data' => json_encode(['usage' => [], 'error' => $e->getMessage()]),
              ])
              ->execute();
          }
        }

        // Done processing taxonomy term.
        return;
      }
      else {
        // Node validation
        $allowed_bundles = $config['content_types'] ?? [];
        if (!in_array($entity->type, $allowed_bundles)) {
          watchdog('openai_embeddings', 'Skipping node ID: @id because its bundle (@bundle) is not allowed.', [
            '@id' => $entity->nid,
            '@bundle' => $entity->type,
          ], WATCHDOG_INFO);
          return;
        }
      }

      // From here, handle nodes and paragraphs normally.
      $supported_field_types = [
        'string', 'text', 'text_long', 'text_with_summary', 'text_textarea_with_summary',
        'paragraphs_embed', 'paragraphs',
        'taxonomy_autocomplete', 'taxonomy_term_reference',
        'text_textarea', 'text_textfield',
      ];

      if ($data['entity_type'] === 'paragraphs_item') {
        $fields = field_info_instances('paragraphs_item', $entity->bundle);
        $entity_id = $entity->item_id;
        $bundle = $entity->bundle;
      }
      else {
        $fields = field_info_instances('node', $entity->type);
        $entity_id = $entity->nid;
        $bundle = $entity->type;
      }

      foreach ($fields as $field_name => $field_info) {
        $widget_type = isset($field_info['widget']['type']) ? $field_info['widget']['type'] : 'undefined';
        $field_info_field = field_info_field($field_name);
        $field_type = isset($field_info_field['type']) ? $field_info_field['type'] : 'undefined';

        $is_taxonomy_field = ($widget_type === 'taxonomy_autocomplete' || $field_type === 'taxonomy_term_reference');

        $combined_type = $is_taxonomy_field ? 'taxonomy_term_reference' : $field_type;

        if (!in_array($combined_type, $supported_field_types)) {
          continue;
        }

        $field_items = field_get_items($data['entity_type'], $entity, $field_name);
        if (empty($field_items)) {
          continue;
        }

        foreach ($field_items as $delta => $item) {
          $text = null;

          if ($is_taxonomy_field) {
            if (!empty($item['tid'])) {
              $term = taxonomy_term_load($item['tid']);
              if ($term) {
                $text = $term->name;
              } else {
                continue;
              }
            } else {
              continue;
            }
          }
          elseif ($widget_type === 'paragraphs_embed' || $field_type === 'paragraphs') {
            if (!empty($item['value'])) {
              $paragraph_queue_item = [
                'entity_id' => $item['value'],
                'entity_type' => 'paragraphs_item',
                'parent_entity_id' => $entity_id,
                'parent_entity_type' => $data['entity_type'],
              ];
              $paragraph = paragraphs_item_load($item['value']);
              if ($paragraph) {
                $paragraph_queue_item['bundle'] = $paragraph->bundle;
                $queue = BackdropQueue::get('embedding_queue');
                $queue->createItem($paragraph_queue_item);
              }
            }
            continue;
          }
          elseif (!empty($item['value'])) {
            $text = $item['value'];
          }
          else {
            continue;
          }

          if (empty($text)) {
            continue;
          }

          foreach ($stopwords as $word) {
            $text = $this->removeStopWord($word, $text);
          }

          $embedding = $openai_api->embedding($text, $model);
          if (empty($embedding)) {
            watchdog('openai_embeddings', 'Failed to generate embedding for entity ID: @id, field: @field.', [
              '@id' => $entity_id,
              '@field' => $field_name,
            ], WATCHDOG_WARNING);
            continue;
          }

          $collection = $data['entity_type'];
          watchdog('openai_embeddings', 'Upserting to collection: @collection', [
            '@collection' => $collection,
          ], WATCHDOG_INFO);
          $unique_id = $this->generateUniqueId($entity, $field_name, $delta, $data['entity_type']);

          $vectors = [
            'id' => $unique_id,
            'values' => $embedding,
            'metadata' => [
              'entity_id' => $entity_id,
              'entity_type' => $data['entity_type'],
              'bundle' => $bundle,
              'field_name' => $field_name,
              'field_delta' => $delta,
              'content' => $text,
            ],
          ];

          if ($data['entity_type'] === 'paragraphs_item' && !empty($data['parent_entity_id'])) {
            $vectors['metadata']['parent_entity_id'] = $data['parent_entity_id'];
            $vectors['metadata']['parent_entity_type'] = $data['parent_entity_type'] ?? 'node';
          }

          try {
            $vector_client->upsert([
              'vectors' => [$vectors],
              'collection' => $collection,
            ]);

            db_merge('openai_embeddings')
              ->key([
                'entity_id' => $entity_id,
                'entity_type' => $data['entity_type'],
                'bundle' => $bundle,
                'field_name' => $field_name,
                'field_delta' => $delta,
                'content' => $text,
              ])
              ->fields([
                'embedding' => json_encode(['data' => $embedding]),
                'data' => json_encode(['usage' => []]),
              ])
              ->execute();

            watchdog('openai_embeddings', 'Successfully stored embedding for entity ID: @id, field: @field', [
              '@id' => $entity_id,
              '@field' => $field_name,
            ], WATCHDOG_INFO);
          }
          catch (\Exception $e) {
            watchdog('openai_embeddings', 'Failed to store embedding in vector database for entity ID: @id, field: @field - @error', [
              '@id' => $entity_id,
              '@field' => $field_name,
              '@error' => $e->getMessage()
            ], WATCHDOG_ERROR);

            // Store in database even if vector database fails
            db_merge('openai_embeddings')
              ->key([
                'entity_id' => $entity_id,
                'entity_type' => $data['entity_type'],
                'bundle' => $bundle,
                'field_name' => $field_name,
                'field_delta' => $delta,
              ])
              ->fields([
                'embedding' => json_encode(['data' => $embedding]),
                'data' => json_encode(['usage' => [], 'error' => $e->getMessage()]),
              ])
              ->execute();

            watchdog('openai_embeddings', 'Saved embedding to database for entity ID: @id, field: @field', [
              '@id' => $entity_id,
              '@field' => $field_name,
            ], WATCHDOG_INFO);
          }
        }
      }
    }
    catch (Exception $e) {
      watchdog('openai_embeddings', 'Error processing queue item: @message', [
        '@message' => $e->getMessage(),
      ], WATCHDOG_ERROR);
    }
  }

  /**
   * Generates a unique ID for the record in the vector database.
   */
  protected function generateUniqueId($entity, $field_name, $delta, $entity_type = 'node') {
    if ($entity_type === 'paragraphs_item') {
      return 'entity:' . $entity->item_id . ':paragraphs_item:' . $entity->bundle . ':' . $field_name . ':' . $delta;
    }
    elseif ($entity_type === 'taxonomy_term') {
      return 'entity:' . $entity->tid . ':taxonomy_term:' . $entity->vocabulary . ':' . $field_name . ':' . $delta;
    }
    else {
      return 'entity:' . $entity->nid . ':node:' . $entity->type . ':' . $field_name . ':' . $delta;
    }
  }

  /**
   * Remove a stop word from text.
   */
  protected function removeStopWord($word, $text) {
    return preg_replace("/\b$word\b/i", '', trim($text));
  }
}
