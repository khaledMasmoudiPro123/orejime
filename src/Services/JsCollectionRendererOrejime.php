<?php

namespace Drupal\orejime\Services;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Asset\JsCollectionRenderer;

/**
 * Renders JavaScript assets.
 */
class JsCollectionRendererOrejime extends JsCollectionRenderer {

  /**
   * {@inheritdoc}
   *
   * This class evaluates the aggregation enabled/disabled condition on a group
   * by group basis by testing whether an aggregate file has been made for the
   * group rather than by testing the site-wide aggregation setting. This allows
   * this class to work correctly even if modules have implemented custom
   * logic for grouping and aggregating files.
   */
  public function render(array $js_assets) {
    $elements = [];

    // A dummy query-string is added to filenames, to gain control over
    // browser-caching. The string changes on every update or full cache
    // flush, forcing browsers to load a new copy of the files, as the
    // URL changed. Files that should not be cached get REQUEST_TIME as
    // query-string instead, to enforce reload on every page request.
    $default_query_string = $this->state->get('system.css_js_query_string') ?: '0';

    // Defaults for each SCRIPT element.
    $element_defaults = [
      '#type' => 'html_tag',
      '#tag' => 'script',
      '#value' => '',
    ];

    // Loop through all JS assets.
    foreach ($js_assets as $js_asset) {
      // Element properties that do not depend on JS asset type.
      $element = $element_defaults;
      $element['#browsers'] = $js_asset['browsers'] ?? [];

      // Element properties that depend on item type.
      switch ($js_asset['type']) {
        case 'setting':
          $element['#attributes'] = [
            // This type attribute prevents this from being parsed as an
            // inline script.
            'type' => 'application/json',
            'data-drupal-selector' => 'drupal-settings-json',
          ];
          $element['#value'] = Json::encode($js_asset['data']);
          break;

        case 'file':
          $query_string = $js_asset['version'] == -1 ? $default_query_string : 'v=' . $js_asset['version'];
          $query_string_separator = (strpos($js_asset['data'], '?') !== FALSE) ? '&' : '?';
          $element['#attributes']['src'] = \Drupal::service('file_url_generator')->generateString($js_asset['data']);
          // Only add the cache-busting query string if this isn't an aggregate
          // file.
          if (!isset($js_asset['preprocessed'])) {
            $element['#attributes']['src'] .= $query_string_separator . ($js_asset['cache'] ? $query_string : \Drupal::time()->getRequestTime());
          }
          break;

        case 'external':
          $element['#attributes']['src'] = $js_asset['data'];
          break;

        default:
          throw new \Exception('Invalid JS asset type.');
      }

      // Attributes may only be set if this script is output independently.
      if (!empty($element['#attributes']['src']) && !empty($js_asset['attributes'])) {
        if (isset($js_asset['attributes']['data-src'])) {
          $js_asset['attributes']['data-src'] = $element['#attributes']['src'];
          unset($element['#attributes']['src']);
        }
        $element['#attributes'] += $js_asset['attributes'];
      }

      $elements[] = $element;
    }

    return $elements;
  }

}
