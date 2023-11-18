<?php

declare(strict_types=1);

namespace Drupal\openai_embeddings;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

interface VectorClientInterface extends ConfigurableInterface, PluginFormInterface, ContainerFactoryPluginInterface {

  public function query(array $parameters);

  public function upsert(array $parameters);

  public function delete(array $parameters);

  public function stats(): array;

}
