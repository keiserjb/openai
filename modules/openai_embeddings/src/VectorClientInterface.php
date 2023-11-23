<?php

declare(strict_types=1);

namespace Drupal\openai_embeddings;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * An interface to define minimum requirements of a vector client plugin.
 */
interface VectorClientInterface extends ConfigurableInterface, PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * Query the vector database.
   *
   * @param array $parameters
   *   A mix of parameters.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response object.
   */
  public function query(array $parameters): ResponseInterface;

  /**
   * Insert or update the vector database.
   *
   * @param array $parameters
   *   A mix of parameters.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response object.
   */
  public function upsert(array $parameters): ResponseInterface;

  /**
   * Delete from the vector database.
   *
   * @param array $parameters
   *   A mix of parameters.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response object.
   */
  public function delete(array $parameters): ResponseInterface;

  /**
   * Provide a render array showing vector database statistics.
   *
   * @return array
   *   A render array of statistics.
   */
  public function stats(): array;

}
