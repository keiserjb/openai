<?php

use GuzzleHttp\Client as GuzzleClient;

/**
 * Base class for vector clients in Backdrop CMS.
 */
abstract class VectorClientBase {

  /**
   * Cached configuration for `openai_embeddings.settings`.
   *
   * @var array
   */
  protected $embeddingsConfig;

  /**
   * Cached configuration for `openai.settings`.
   *
   * @var array
   */
  protected $openaiConfig;

  /**
   * Constructor to load configurations.
   */
  public function __construct() {
    $this->embeddingsConfig = config_get('openai_embeddings.settings');
    $this->openaiConfig = config_get('openai.settings');
  }

  /**
   * Retrieve the OpenAI API key.
   *
   * @return string|null
   *   The OpenAI API key or NULL if not set.
   *
   * @throws \Exception
   *   If the key value cannot be resolved.
   */
  public function getOpenAIKey(): ?string {
    $key = $this->openaiConfig['api_key'] ?? NULL;

    if ($key) {
      $resolved = key_get_key_value($key);
      if (!empty($resolved)) {
        return $resolved;
      }
      throw new \Exception('The OpenAI API key could not be resolved using the Key module.');
    }

    return NULL;
  }

  /**
   * Resolve configuration values for vector client settings.
   *
   * @param string $key
   *   The key in the configuration array.
   * @param string $description
   *   A description of the setting for debugging purposes.
   * @param bool $resolve_key
   *   Whether to use the Key module for resolution.
   *
   * @return string
   *   The resolved configuration value.
   *
   * @throws \Exception
   *   If the configuration is invalid or missing.
   */
  protected function resolveConfigValue(string $key, string $description, bool $resolve_key = FALSE): string {
    $value = $this->embeddingsConfig[$key] ?? NULL;

    // Log raw configuration value for debugging.
    watchdog('openai_embeddings', "Step 1: Raw config for $key: @value", [
      '@value' => $value ?? 'NULL',
    ], WATCHDOG_DEBUG);

    // Resolve key values if required.
    if ($resolve_key && $value) {
      $resolved = key_get_key_value($value);
      if (!empty($resolved)) {
        return trim($resolved);
      }
      throw new \Exception("The $description key could not be resolved using the Key module.");
    }

    // Validate the value.
    if (empty($value) || !is_string($value)) {
      throw new \Exception("Invalid or missing $description in configuration.");
    }

    return trim($value);
  }

  /**
   * Initialize an HTTP client with default options.
   *
   * @param array $options
   *   Additional options for the HTTP client.
   *
   * @return \GuzzleHttp\Client
   *   A configured Guzzle HTTP client.
   */
  public function getHttpClient(array $options = []): GuzzleClient {
    $default_options = [
      'headers' => ['Content-Type' => 'application/json'],
    ];
    return new GuzzleClient(array_merge($default_options, $options));
  }

  /**
   * Log payload data for debugging purposes.
   *
   * @param string $operation
   *   The operation being performed (e.g., 'query', 'upsert').
   * @param array $payload
   *   The payload data to log.
   */
  protected function logPayload(string $operation, array $payload): void {
    watchdog('openai_embeddings', "Payload for $operation: @payload", [
      '@payload' => json_encode($payload, JSON_PRETTY_PRINT),
    ], WATCHDOG_DEBUG);
  }

  /**
   * Log response data for debugging purposes.
   *
   * @param string $operation
   *   The operation being performed (e.g., 'query', 'upsert').
   * @param mixed $response
   *   The response data to log.
   */
  protected function logResponse(string $operation, $response): void {
    watchdog('openai_embeddings', "Response from $operation: @response", [
      '@response' => json_encode($response, JSON_PRETTY_PRINT),
    ], WATCHDOG_DEBUG);
  }

  /**
   * Handle exceptions and log errors.
   *
   * @param string $operation
   *   The operation being performed (e.g., 'query', 'upsert').
   * @param \Exception $e
   *   The exception to handle.
   *
   * @throws \Exception
   *   Re-throws the exception after logging it.
   */
  protected function handleError(string $operation, \Exception $e): void {
    $message = $e instanceof GuzzleHttp\Exception\RequestException && $e->hasResponse()
      ? $e->getResponse()->getBody()->getContents()
      : $e->getMessage();

    watchdog('openai_embeddings', "Error during $operation: @message", [
      '@message' => $message,
    ], WATCHDOG_ERROR);

    throw $e;
  }

  /**
   * Abstract methods to be implemented by child classes.
   */
  abstract public function query(array $parameters);
  abstract public function upsert(array $parameters);
  abstract public function stats();
}
