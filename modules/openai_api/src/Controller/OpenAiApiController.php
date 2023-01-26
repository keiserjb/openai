<?php

namespace Drupal\openai_api\Controller;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\openai_api\OpenAIService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for openai api routes.
 */
class OpenAIApiController extends ControllerBase {

  const MODELS_OPTIONS = [
    'text-davinci-003',
    'text-curie-001',
    'text-babbage-001',
    'text-ada-001'
  ];

  /**
   * Drupal\profiler\Config\ConfigFactoryWrapper definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Defining the openAiService object.
   *
   * @var \Drupal\openai_api\OpenAIService
   */
  protected OpenAIService $openAiService;

  /**
   * Defining a constructor for dependencies.
   *
   * @param \Drupal\openai_api\OpenAIService $openaiService
   *   The openAIService object.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory The config
   *   factory.
   */
  public function __construct(OpenAIService $openaiService, ConfigFactory $config_factory) {
    $this->openAiService = $openaiService;
    $this->configFactory = $config_factory;
  }

  /**
   * Creates a new service.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The ContainerInterface object.
   *
   * @return \Drupal\openai_api\Controller\OpenAIApiController
   *   The OpenAIApiController object.
   */
  public static function create(ContainerInterface $container): OpenAIApiController {
    return new static(
      $container->get('openai_api.openai.service'),
      $container->get('openai_api.settings')
    );
  }

  /**
   * Builds the response for text.
   *
   * @param string $model The api model.
   * @param string $text The text for generation.
   * @param int $max_token The maximum number of tokens.
   * @param float $temperature The temperature.
   *
   * @return string
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getTextCompletionResponseBodyData(
    $model,
    $text,
    $max_token,
    $temperature
  ): string {
    $textCall = $this->openAiService->getText(
      $model,
      $text,
      $max_token,
      $temperature
    );

    if ($textCall->getStatusCode() === 200) {
      $completion = $this->openAiService->getResponseBody($textCall);
      $return = $completion['choices'][0]['text'];
    }
    else {
      $return = '';
    }

    return $return;
  }

  /**
   * Builds the response for image.
   *
   * @param $prompt
   * @param $size
   *
   * @return string
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getImageUrlResponseBodyData(
    $prompt,
    $size,
  ): string {
    $imgCall = $this->openAiService->getImageUrl(
      $prompt,
      $size,
    );

    if ($imgCall->getStatusCode() === 200) {
      $imgUrl = $this->openAiService->getResponseBody($imgCall);
      $return = $imgUrl['data'][0]['url'];
    }
    else {
      $return = '';
    }

    return $return;
  }

  /**
   * Gets the Model Response Body Data.
   *
   * @return array
   *   An array containing the data.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getModelsResponseBodyData(): array {
    $modelsCall = $this->openAiService->getModels();

    if ($modelsCall->getStatusCode() === 200) {
      $models = $this->openAiService->getResponseBody($modelsCall);
      $return = $models['data'];
    }
    else {
      $return = [];
    }

    return $return;
  }

  /**
   * Gets the models.
   *
   * @return array
   *   Returns an array of models.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getModels(): array {
    $models_list = $this->getModelsResponseBodyData();
    $models = [];

    foreach ($models_list as $item) {
      if (in_array($item['id'], self::MODELS_OPTIONS)) {
        $models[$item['id']] = $item['id'];
      }
    }

    return $models;
  }

  /**
   * Generates an article.
   * @param array $data
   *   The array data of the article.
   * @param string $body
   *   The body of the article.
   * @param null|string $img
   *   The img url.
   *
   * @return int
   *   Returns an int indicating whether the article was saved or not.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function generateArticle(array $data, string $body, ?string $img = NULL): int {
    $config = $this->configFactory->get('openai_api.settings');

    $article = Node::create(['type' => $config->get('content_type')]);
    $article->set($config->get('field_title'), $data['subject']);
    $article->set($config->get('field_body'), $body);

    // Set article img if prompt are provided in form.
    if ($img !== NULL) {
      $file = $this->generate_media_image($data, $img);
      if ($file->id()) {
        $article->set($config->get('field_image'), [
          'target_id' => $file->id(),
          'alt' => 'article-illustration',
          'title' => 'illustration',
        ]);
      }
    }
    $article->enforceIsNew();

    return $article->save();
  }

  /**
   * @param string|null $img
   * @param array $data
   *
   * @return \Drupal\file\Entity\File
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function generate_media_image(array $data, ?string $img = NULL): File {
    /** @var \Drupal\file\Entity\File $file */
    $file = system_retrieve_file($img, 'public://', TRUE, FileSystemInterface::EXISTS_REPLACE);
    if ($file && $file->id()) {
      $this->createDerivativesImageStyle($file);
      $this->createMediaImage($file, $data);
    }

    return $file;
  }

  /**
   * @param $file
   *
   * @return void
   */
  public function createDerivativesImageStyle($file): void {
    $styles = ImageStyle::loadMultiple();

    if ($styles) {
      /** @var \Drupal\image\Entity\ImageStyle $style */
      foreach ($styles as $style) {
        $uri = $file->getFileUri();
        $destination = $style->buildUri($uri);
        if (!file_exists($destination)) {
          $style->createDerivative($uri, $destination);
        }
      }
    }
  }

  /**
   * @param \Drupal\file\Entity\File $file
   * @param array $data
   *
   * @return void
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createMediaImage(File $file, array $data): void {
    $username = \Drupal::currentUser()->getAccountName();
    $timestamp = \Drupal::time()->getCurrentTime();
    $date = \Drupal::service('date.formatter')->format($timestamp, 'custom', 'Y-m-dTH:i:s');
    $mediaName = strtolower(str_replace(' ', '-', $data['image_prompt']));

    $image_media = Media::create([
      'name' => $mediaName.'.png',
      'bundle' => 'image',
      'uid' => 1,
      'langcode' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
      'status' => 1,
      'field_media_image' => [
        'target_id' => $file->id(),
        'alt' => $data['image_prompt'],
        'title' => $data['image_prompt'],
      ],
      'field_author' => strtolower(str_replace(' ', '-', $username)),
      'field_date' => $date,
    ]);
    $image_media->save();
  }

  /**
   * Gets the OpenAI Subjects.
   *
   * @return array
   *   An array containing the options
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getSubjectsVocabularyTerms(): array {

    $config = $this->configFactory->get('openai_api.settings');
    $subjects = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term')->loadTree(
    // The taxonomy term vocabulary machine name.
      $config->get('vocabulary'),
      // The "tid" of parent using "0" to get all.
      0,
      // Get only 1st level.
      1,
      // Get full load of taxonomy term entity.
      TRUE
    );

    $options = [];
    /** @var \Drupal\taxonomy\Entity\Term $subject */
    foreach ($subjects as $subject) {
      $options[$subject->getName()] = $subject->getName();
    }

    return $options;
  }

}
