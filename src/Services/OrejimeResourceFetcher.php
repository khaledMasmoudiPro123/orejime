<?php

namespace Drupal\orejime\services;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\media\OEmbed;
use Drupal\media\OEmbed\ProviderRepositoryInterface;
use Drupal\media\OEmbed\Resource;
use GuzzleHttp\ClientInterface;

class OrejimeResourceFetcher extends OEmbed\ResourceFetcher {

  /**
   * @var ImmutableConfig
   */
  protected ImmutableConfig $settings;

  /**
   * Constructs a ResourceFetcher object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\media\OEmbed\ProviderRepositoryInterface $providers
   *   The oEmbed provider repository service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(ClientInterface $http_client, ProviderRepositoryInterface $providers, CacheBackendInterface $cache_backend, ConfigFactoryInterface $config_factory) {
    parent::__construct($http_client, $providers, $cache_backend);
    $this->settings = $config_factory->get('orejime.settings');
  }

  /**
   * @inheritDoc
   */
  protected function createResource(array $data, $url): Resource {
    if($this->settings->get('iframe_consent')) {
      $this->changeUrl($data);
    }
    return parent::createResource($data, $url);
  }

  /**
   * Add Autoplay for YouTube and Vimeo.
   *
   * @param array $data
   *
   * @author Fabien Gutknecht <fgutknecht@gaya.fr>
   */
  private function changeUrl(array &$data): void {
    preg_match('/src="([^"]+)"/', $data['html'], $match);
    if (isset($match[1])) {
      $url = $match[1];
      switch ($data['provider_name']) {
        case 'Vimeo':
        case 'YouTube':
          $url .= '&autoplay=1';
          break;
      }
      $data['html'] = str_replace($match[1], $url, $data['html']);
    }
  }

}

