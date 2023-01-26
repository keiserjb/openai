<?php

namespace Drupal\openai_api;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Messenger\MessengerInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

/**
 * Defines functionality related to openai API.
 */
class OpenAIService {

  /**
   * Drupal\profiler\Config\ConfigFactoryWrapper definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected ConfigFactory $configFactory;

  /**
   * Defines the url for the API.
   *
   * @var string|null
   */
  protected ?string $apiUrl = null;

  /**
   * Defines the token for the API.
   *
   * @var string|null
   */
  protected ?string $apiToken = null;

  /**
   * Defines the URI for the API.
   *
   * @var string|null
   */
  protected ?string $apiUri;

  /**
   * Constructs a new OpenAIService object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory The config
   *   factory.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
    $config = $this->configFactory->get('openai_api.settings');
    $apiUrl = $config->get('api_url');
    $apiToken = $config->get('api_token');

    if (!empty($apiUrl) && !empty($apiToken)) {
      $this->apiUrl = $apiUrl;
      $this->apiToken = $apiToken;
    }
  }

  /**
   * Gets the text.
   *
   * @param string $models
   *   Defines the models.
   * @param string $text
   *   Defines the text.
   * @param int $max_token
   *   Defines the max token.
   * @param float $temperature
   *   Defines the temperature.
   *
   * @return \GuzzleHttp\Psr7\Response|\Psr\Http\Message\ResponseInterface
   *   Returns a response interface.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getText(string $models, string $text, int $max_token, float $temperature): Response|ResponseInterface {
    $this->setApiUri('/completions');

    $content = [
      "model" => $models,
      "prompt" => $text . '.',
      "max_tokens" => $max_token,
      "temperature" => $temperature,
    ];

    $params = array_merge(['json' => $content], $this->_getHeaderParams());

    return $this->callApi('post', $params);
  }

  /**
   * @param string $prompt
   * @param string $size
   *
   * @return \GuzzleHttp\Psr7\Response|\Psr\Http\Message\ResponseInterface
   *   Returns a response interface.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getImageUrl(string $prompt, string $size): Response|ResponseInterface {
    $this->setApiUri('/images/generations');

    $content = [
      "prompt" => $prompt,
      "n" => 1,
      "size" => $size,
    ];

    $params = array_merge(['json' => $content], $this->_getHeaderParams());

    return $this->callApi('post', $params);
  }

  /**
   * Gets the models.
   *
   * @return \GuzzleHttp\Psr7\Response|\Psr\Http\Message\ResponseInterface
   *   Returns a response interface.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getModels(): Response|ResponseInterface {
    $this->setApiUri('/models');
    $params = array_merge([], $this->_getHeaderParams());

    return $this->callApi('get', $params);
  }

  /**
   * All API call.
   *
   * @param mixed $method
   *   Takes in the HTTP method.
   * @param array $params
   *   Takes in an array of relevant parameters.
   *
   * @return \GuzzleHttp\Psr7\Response|\Psr\Http\Message\ResponseInterface
   *   Returns a response interface.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function callApi($method, array $params = []): Response|ResponseInterface {
    $uri = $this->getApiUrl() . $this->getApiUri();
    $params = count($params) ? $params : [];

    try {
      $response = $this->getClient()->request($method, $uri, $params);
    } catch (RequestException $e) {
      $response = new Response(
        $e->getCode(), $e->getResponse()
        ->getHeaders(), $e->getResponse()->getBody()
      );
    }

    return $response;
  }

  /**
   * Gets the header params.
   *
   * @return array|\string[][]
   *   An array/string indicating the header params.
   */
  private function _getHeaderParams(): array {
    $header = [
      'headers' => [
        'content-type' => 'application/json;charset=utf-8',
        'Accept' => 'application/json',
      ],
    ];
    $header['headers']['Authorization'] = "Bearer " . $this->getApiToken();

    return $header;
  }

  /**
   * Gets the API Url.
   *
   * @return string|null apiUrl
   *   A string indicating the API url.
   */
  public function getApiUrl(): mixed {
    return $this->apiUrl;
  }

  /**
   * Gets the API token.
   *
   * @return string|null apiToken
   *   A string indicating the API token.
   */
  public function getApiToken(): mixed {
    return $this->apiToken;
  }

  /**
   * Gets the API URI.
   *
   * @return string|null apiUri
   *   A string indicating the API URI.
   */
  public function getApiUri(): ?string {
    return $this->apiUri;
  }

  /**
   * Sets the API URI.
   *
   * @param string|null $apiUri
   *   A string indicating API URI.
   */
  public function setApiUri(?string $apiUri): void {
    $this->apiUri = $apiUri;
  }

  /**
   * Gets the HTTP Client Object.
   *
   * @return \GuzzleHttp\Client
   *   The client object.
   */
  public function getClient(): Client {
    return new Client();
  }

  /**
   * Gets the Response body.
   *
   * @param object $response
   *   Takes in the response object.
   *
   * @return mixed|void
   *   Returns the json decoded body.
   */
  public static function getResponseBody(Response $response): array {
    if (gettype($response) !== 'boolean') {
      return \json_decode($response->getBody()->getContents(), TRUE);
    }
  }
}
