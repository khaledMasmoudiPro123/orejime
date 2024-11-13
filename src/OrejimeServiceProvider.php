<?php

namespace Drupal\orejime;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Renders JavaScript assets.
 */
class OrejimeServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides language_manager class to test domain language negotiation.
    // Adds entity_type.manager service as an additional argument.
    $definition = $container->getDefinition('asset.js.collection_renderer');
    $definition->setClass('Drupal\orejime\Services\JsCollectionRendererOrejime');

    $definition = $container->getDefinition('media.oembed.resource_fetcher');
    $definition->setClass('Drupal\orejime\Services\OrejimeResourceFetcher')
    ->addArgument(new Reference('config.factory'));
  }

}
