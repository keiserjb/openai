<?php

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
    // Load configuration once during class initialization.
    $this->embeddingsConfig = config_get('openai_embeddings.settings');
    $this->openaiConfig = config_get('openai.settings');
  }

  /**
   * Get OpenAI API key.
   *
   * @return string|null
   *   OpenAI API key, or NULL if not set.
   */
  public function getOpenAIKey(): ?string {
    return $this->openaiConfig['api_key'] ?? NULL;
  }

  /**
   * Get Pinecone or Milvus configuration based on vector client settings.
   *
   * @param string $key
   *   The configuration key to retrieve.
   *
   * @return mixed|null
   *   The configuration value or NULL if not set.
   */
  public function getEmbeddingConfig(string $key) {
    return $this->embeddingsConfig[$key] ?? NULL;
  }

  /**
   * Initialize an HTTP client with appropriate headers and base URI.
   *
   * @param array $options
   *   Additional options for the HTTP client.
   *
   * @return \GuzzleHttp\Client
   *   The configured Guzzle HTTP client.
   */
  public function getHttpClient(array $options = []): \GuzzleHttp\Client {
    // Default options can be extended by derived classes.
    $default_options = [
      'headers' => ['Content-Type' => 'application/json'],
    ];
    return new \GuzzleHttp\Client(array_merge($default_options, $options));
  }

  /**
   * Abstract methods for child classes.
   */
  abstract public function query(array $parameters);
  abstract public function upsert(array $parameters);
  abstract public function stats();
}



