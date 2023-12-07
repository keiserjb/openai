<?php

namespace Drupal\openai_eca\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;

/**
 * Describes the OpenAI openai_eca_execute_moderation action.
 *
 * @Action(
 *   id = "openai_eca_execute_moderation",
 *   label = @Translation("OpenAI/ChatGPT Moderation"),
 *   description = @Translation("Determine if a piece of text violates any OpenAI usage policies.")
 * )
 */
class Moderation extends OpenAIActionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
        'token_input' => '',
        'token_result' => '',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['token_input'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token input'),
      '#default_value' => $this->configuration['token_input'],
      '#description' => $this->t('The data input for OpenAI.'),
      '#weight' => -10,
      '#eca_token_reference' => TRUE,
    ];

    $form['token_result'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token result'),
      '#default_value' => $this->configuration['token_result'],
      '#description' => $this->t('The response from OpenAI will be stored into the token result field to be used in future steps.'),
      '#weight' => -9,
      '#eca_token_reference' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['token_input'] = $form_state->getValue('token_input');
    $this->configuration['token_result'] = $form_state->getValue('token_result');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $token_value = trim($this->tokenServices->getTokenData($this->configuration['token_input']));
    $response = $this->api->moderation($token_value);
    $this->tokenServices->addTokenData($this->configuration['token_result'], $response);
  }

}
