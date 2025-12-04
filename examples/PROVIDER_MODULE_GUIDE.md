# OpenAI Provider Module Guide

This document explains how to create a pluggable provider module for the `openai` Backdrop module (examples: `openai_openrouter`, `openai_ollama`, `openai_litellm`). Drop this file into `modules/contrib/openai/` so it's shipped with the main module.

Goal
- Build a small provider module that registers itself with `openai` via `hook_openai_provider_info()` and implements an adapter class (in `includes/`) exposing methods the `openai` core expects.

Checklist
- [ ] Create module folder `modules/contrib/openai_<provider>/`
- [ ] Add `<module>.info` with metadata
- [ ] Implement `hook_autoload_info()` to register the adapter class
- [ ] Implement `hook_openai_provider_info()` with provider metadata
- [ ] Implement `hook_openai_provider_settings_alter()` to add provider settings to central OpenAI settings form
- [ ] Add an adapter class in `includes/` implementing the required methods (see skeleton)
- [ ] Enable module, configure provider in OpenAI settings, and test

Design notes
- Providers register metadata (id, label, adapter class) so `openai` can list and use them.
- The Key module should store API keys; use `#type => 'key_select'` in provider settings to avoid storing secrets in plaintext.
- The `openai` core expects provider adapters to provide specific helper methods (see adapter skeleton).

Files & structure

modules/contrib/openai_myprovider/
- openai_myprovider.info
- openai_myprovider.module
- includes/MyProviderAdapter.php
- README.md (optional)

Minimal `openai_myprovider.info` (Backdrop) - replace myprovider with your id

```ini
name = OpenAI MyProvider
description = Integrates MyProvider with the OpenAI module
core = 1.x
package = OpenAI
version = "1.0"
```

Add `hook_autoload_info()` so Backdrop can load the adapter class automatically:

```php
function openai_myprovider_autoload_info() {
  return [
    'MyProviderAdapter' => 'includes/MyProviderAdapter.php',
  ];
}
```

Register the provider with `openai` via `hook_openai_provider_info()`:

```php
function openai_myprovider_openai_provider_info() {
  return [
    'myprovider' => [
      'label' => t('MyProvider'),
      'description' => t('MyProvider API integration.'),
      'class' => 'MyProviderAdapter',
      'website' => 'https://myprovider.example',
      'key_url' => 'https://myprovider.example/account/api-keys', // optional
      'models_url' => 'https://myprovider.example/models', // optional
    ],
  ];
}
```

Provider settings: add fields into the central OpenAI settings form so admins can enable/configure the provider without leaving OpenAI settings.

```php
function openai_myprovider_openai_provider_settings_alter(&$settings, $context) {
  if ($context['provider'] !== 'myprovider') {
    return;
  }

  // If your provider uses API keys stored via Key module
  $available_keys = key_get_key_names_as_options();
  $settings['api_key_myprovider'] = [
    '#type' => 'key_select',
    '#title' => t('MyProvider API Key'),
    '#default_value' => config_get('openai.settings', 'api_key_myprovider') ?: '',
    '#options' => $available_keys,
    '#key_filters' => ['type' => 'authentication'],
    '#description' => t('Add your API key via the Key module and select it here.'),
  ];

  // If you need a base URL (for local providers like Ollama)
  $settings['myprovider_base_url'] = [
    '#type' => 'textfield',
    '#title' => t('MyProvider Base URL'),
    '#default_value' => config_get('openai_myprovider.settings', 'base_url') ?: 'http://localhost:11434',
    '#description' => t('Base URL of the local MyProvider server.'),
  ];

  // Save provider-specific settings via a submit handler if needed
  if (isset($context['form'])) {
    $context['form']['#submit'][] = 'openai_myprovider_settings_submit';
  }
}

function openai_myprovider_settings_submit($form, &$form_state) {
  if (!empty($form_state['values']['myprovider_base_url'])) {
    $c = config('openai_myprovider.settings');
    $c->set('base_url', $form_state['values']['myprovider_base_url']);
    $c->save();
  }
}
```

Adapter class skeleton - implement provider-specific calls

Place `includes/MyProviderAdapter.php` and define the adapter class. The central `openai` code expects adapter instances to expose methods such as `getModels()`, `getChatModels()`, `getImageModels()`, `getVisionModels()`, `getEmbeddingModels()`, `getModerationModels()`, and operational methods `chat()`, `images()`, `moderation()`, `embed()`.

```php
<?php
class MyProviderAdapter {
  protected $api_key;
  protected $provider_id;

  public function __construct($api_key = NULL, $provider_id = 'myprovider') {
    $this->api_key = $api_key;
    $this->provider_id = $provider_id;
  }

  // Model discovery - return arrays: model_id => friendly name
  public function getModels() {
    // Fetch all available models from your provider's API
    // Return associative array: ['model-id' => 'Display Name', ...]
    return [];
  }

  /**
   * Filter models by capability.
   *
   * This helper method is used by the capability-specific methods below.
   * Implement logic to detect which models support which capabilities,
   * either via API metadata or pattern matching.
   *
   * @param string $capability
   *   The capability: 'text', 'vision', 'image', 'embeddings', 'moderation'.
   *
   * @return array
   *   Filtered array of model_id => display name.
   */
  public function getModelsByCapability($capability): array {
    $all_models = $this->getModels();
    $filtered = [];

    foreach ($all_models as $id => $name) {
      $ok = FALSE;

      // Implement your capability detection logic here
      // This could be:
      // - Checking API metadata (if your provider returns capability info)
      // - Pattern matching on model names
      // - Hardcoded lists of known models
      switch ($capability) {
        case 'text':
          // Detect text/chat models
          $ok = TRUE; // Most models support text
          break;

        case 'image':
          // Detect image generation models
          $ok = strpos($id, 'dall-e') !== FALSE || strpos($id, 'stable-diffusion') !== FALSE;
          break;

        case 'vision':
          // Detect vision models (image input)
          $ok = strpos($id, 'vision') !== FALSE || strpos($id, 'gpt-4o') !== FALSE;
          break;

        case 'embeddings':
        case 'embedding':
          // Detect embedding models
          $ok = strpos($id, 'embed') !== FALSE;
          break;

        case 'moderation':
          // Detect moderation models
          $ok = strpos($id, 'moderation') !== FALSE;
          break;
      }

      if ($ok) {
        $filtered[$id] = $name;
      }
    }

    // Allow site-specific overrides via Backdrop's alter hook system
    // This lets site admins override capability detection in custom modules
    backdrop_alter('openai_model_capabilities', $filtered, $capability, $this->provider_id);

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

  // Operations
  public function chat($model, array $messages, $temperature = 0.7, $max_tokens = 1024, $stream_response = FALSE) {
    // Implement chat request; return text or structured as openai core expects
    return '';
  }

  public function images($model, $prompt, $options = []) {
    // Implement image generation; return array with data or URLs
    return [];
  }

  public function moderation($input, $model = NULL) {
    // Implement moderation call
    return [];
  }

  public function embed($model, $input) {
    // Implement embedding call
    return [];
  }
}
```

## Model Capability Detection

Different providers handle model capabilities differently:

### OpenAI
OpenAI's API returns capability metadata, so the adapter can reliably detect which models support which features.

### Ollama
Ollama provides some capability metadata via its native API (e.g., 'families' array), but it's not always complete. The adapter uses a combination of:
- API metadata when available
- Pattern matching on model names (e.g., 'llava' for vision, 'embed' for embeddings)
- The `backdrop_alter('openai_model_capabilities')` hook for site-specific overrides

### OpenRouter
OpenRouter doesn't provide reliable capability metadata, so the adapter primarily relies on:
- Pattern matching on model names
- The `backdrop_alter('openai_model_capabilities')` hook for site-specific overrides

### Best Practices

1. **Use `getModelsByCapability()`**: This helper method centralizes capability detection logic
2. **Support the alter hook**: Always call `backdrop_alter('openai_model_capabilities')` to allow site admins to override your detection
3. **Document limitations**: If your provider doesn't reliably report capabilities, document this and provide examples of using the alter hook
4. **Provide fallbacks**: When in doubt, return all models and let users filter via the alter hook

See `modules/contrib/openai/examples/model_capability_override.php` for examples of using the alter hook.
