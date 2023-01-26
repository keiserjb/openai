<?php

namespace Drupal\openai_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Configure OpenAI api settings for this site.
 */
class ApiSettingsForm extends ConfigFormBase {

  const NOT_ALLOWED_FIELDS = [
    'nid',
    'uuid',
    'vid',
    'langcode',
    'revision_timestamp',
    'revision_uid',
    'revision_log',
    'status',
    'uid',
    'created',
    'changed',
    'promote',
    'sticky',
    'default_langcode',
    'revision_default',
    'revision_translation_affected',
    'path',
    'type',
  ];

  /**
   * {@inheritdoc}
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'openai_api_api_settings';
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   */
  protected function getEditableConfigNames() {
    return ['openai_api.settings'];
  }


  /**
   * {@inheritdoc}
   *
   * @param array $form The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state The form state.
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['api_settings_container'] = [
      '#type' => 'details',
      '#title' => $this->t('API settings'),
      '#open' => FALSE,
      '#required' => TRUE,
    ];

    $form['api_settings_container']['api_token'] = [
      '#required' => TRUE,
      '#type' => 'textarea',
      '#title' => $this->t('OpenAI token'),
      '#default_value' => $this->config('openai_api.settings')
        ->get('api_token'),
      '#description' => $this->t('Get your API token authentication on <a href="https://openai.com/">OpenAI</a>'),
    ];
    $form['api_settings_container']['api_url'] = [
      '#required' => TRUE,
      '#type' => 'textfield',
      '#title' => $this->t('OpenAI url'),
      '#placeholder' => 'https://api.openai.com/v1',
      '#default_value' => 'https://api.openai.com/v1',
      '#description' => $this->t('Should be <b>https://api.openai.com/v1</b>'),
    ];

    $form['article_settings_container'] = [
      '#type' => 'details',
      '#title' => $this->t('Article settings'),
      '#open' => TRUE,
      '#required' => TRUE,
    ];

    $types = [];
    $contentTypes = \Drupal::service('entity_type.manager')
      ->getStorage('node_type')
      ->loadMultiple();
    foreach ($contentTypes as $contentType) {
      $types[$contentType->id()] = $contentType->label();
    }
    $form['article_settings_container']['content_type'] = [
      '#required' => TRUE,
      '#type' => 'select',
      '#title' => $this->t('Content type'),
      '#options' => $types,
      '#empty_option' => $this->t("- Choose a content type -"),
      '#description' => $this->t("Content type for generating article."),
      '#default_value' => $this->config('openai_api.settings')
        ->get('content_type'),
      '#ajax' => [
        'callback' => '::rebuild_form',
        'effect' => 'fade',
        'wrapper' => 'js-article-setting-wrapper',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    $form['article_settings_container']['fields_container'] = [
      '#type' => 'container',
      '#prefix' => '<div id="js-article-setting-wrapper">',
      '#suffix' => '</div>',
    ];

    $field_options = $this->get_field_options($form, $form_state);
    $form['article_settings_container']['fields_container']['field_title'] = [
      '#type' => 'select',
      '#title' => t('Field for title'),
      '#options' => !empty($field_options) ? $field_options : ['' => "- No field -"],
      '#description' => $this->t("Choose a field for your article title."),
      '#default_value' => $this->config('openai_api.settings')
        ->get('field_title'),
    ];

    $form['article_settings_container']['fields_container']['field_body'] = [
      '#type' => 'select',
      '#title' => t('Field for body'),
      '#options' => !empty($field_options) ? $field_options : ['' => "- No field -"],
      '#description' => $this->t("Choose a field for your article body."),
      '#default_value' => $this->config('openai_api.settings')
        ->get('field_body'),
    ];

    $form['article_settings_container']['fields_container']['field_image'] = [
      '#type' => 'select',
      '#title' => t('Field for image'),
      '#options' => !empty($field_options) ? $field_options : ['' => "- No field -"],
      '#description' => $this->t("Choose a field for your article image."),
      '#default_value' => $this->config('openai_api.settings')
        ->get('field_image'),
    ];

    $vocabulary = $this->get_vocabulary_options();
    $form['article_settings_container']['vocabulary'] = [
      '#type' => 'select',
      '#title' => t('Subject vocabulary'),
      '#options' => !empty($vocabulary) ? $vocabulary : ['' => "- Choose a vocabulary -"],
      '#description' => $this->t("Choose a vocabulary for filling subjects."),
      '#default_value' => $this->config('openai_api.settings')
        ->get('vocabulary'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function rebuild_form($form, &$form_state) {
    $form_state->setRebuild();
    return $form['article_settings_container']['fields_container'];
  }

  public function get_field_options($form, $form_state): array {
    $options = [];
    $content_type = $form_state->getValue('content_type');
    if ($content_type == null) {
      $content_type = $this->config('openai_api.settings')->get('content_type');
    }
    if ($content_type !== NULL) {
      $entityFieldManager = \Drupal::service('entity_field.manager');
      $fields = $entityFieldManager->getFieldDefinitions('node', $content_type);

      /** @var \Drupal\Core\Field\FieldDefinition $field */
      foreach ($fields as $field) {
        if (!in_array($field->getName(), self::NOT_ALLOWED_FIELDS)) {
          $options[$field->getName()] = $field->getName();
        }
      }
    }

    return $options;
  }

  public function get_vocabulary_options(): array {
    $vocabulary_options = [];
    $vocabularies = Vocabulary::loadMultiple();

    /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary */
    foreach ($vocabularies as $vocabulary) {
      $vocabulary_options[$vocabulary->id()] = $vocabulary->label();
    }

    return $vocabulary_options;
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state The form state.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('openai_api.settings')
      ->set('api_token', $form_state->getValue('api_token'))
      ->set('api_url', $form_state->getValue('api_url'))
      ->set('content_type', $form_state->getValue('content_type'))
      ->set('vocabulary', $form_state->getValue('vocabulary'))
      ->set('field_title', $form_state->getValue('field_title'))
      ->set('field_body', $form_state->getValue('field_body'))
      ->set('field_image', $form_state->getValue('field_image'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
