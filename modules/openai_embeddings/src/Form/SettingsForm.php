<?php

namespace Drupal\openai_embeddings\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Configure OpenAI Embeddings settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openai_embeddings_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['openai_embeddings.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $types = NodeType::loadMultiple();
    $options = [];

    foreach ($types as $type) {
      $options[$type->id()] = $type->label();
    }

    $form['node_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enable analysis of these node types'),
      '#options' => $options,
      '#default_value' => $this->config('openai_embeddings.settings')->get('node_types'),
      '#description' => $this->t('Select which node types should be analyzed. Note that more content that you analyze will use more of your API usage. Check your <a href="@link">OpenAI account</a> for usage and billing details.', ['@link' => 'https://platform.openai.com/account/usage']),
    ];

    // @todo Let the user select which view mode to render and submit for analysis

    $form['model'] = [
      '#type' => 'select',
      '#title' => $this->t('Model to use'),
      '#options' => [
        'text-embedding-ada-002' => 'text-embedding-ada-002',
      ],
      '#default_value' => $this->config('openai_embeddings.settings')->get('model'),
      '#description' => $this->t('Select which model to use to analyze text. See the <a href="@link">model overview</a> for details about each model.', ['@link' => 'https://platform.openai.com/docs/guides/embeddings/embedding-models']),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('openai_embeddings.settings')
      ->set('node_types', array_filter($form_state->getValue('node_types')))
      ->set('model', $form_state->getValue('model'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
