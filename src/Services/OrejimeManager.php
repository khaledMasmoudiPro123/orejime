<?php

namespace Drupal\orejime\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Manage orejime.
 */
class OrejimeManager {

  /**
   * The Orejime config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The condition manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $conditionManager;

  /**
   * Creates a new PathMessageEventSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $condition_manager
   *   The condition manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ExecutableManagerInterface $condition_manager, LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->config = $config_factory->get('orejime.settings');
    $this->conditionManager = $condition_manager;
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Test if the current page is ignored or not.
   *
   * @return bool
   *   The result of the test.
   */
  public function ignoreCurrentPage() {
    $value = FALSE;
    $manager = $this->conditionManager->createInstance('request_path');
    $request_path = $this->config->get('request_path');
    if (isset($request_path)) {
      $manager->setConfiguration($request_path);
      $value = $manager->evaluate();
      if ($this->config->get('request_path.negate') === 1) {
        $value = !$value;
      }
    }
    return $value;
  }

  /**
   * Get services Orejime for Cookie Manage.
   *
   * @return array
   *   List of services formatted.
   */
  public function getServicesManage() {
    $return = &drupal_static('get_orejime_services_manage');
    if (!$return) {
      $return = ['services' => [], 'purposes' => []];
      $purposes = [];
      $services = $this->entityTypeManager->getStorage('orejime_service')->loadMultiple($this->getAllServices());
      $lg = $this->languageManager->getCurrentLanguage()->getId();
      foreach ($services as $service) {
        if ($service->hasTranslation($lg)) {
          $service = $service->getTranslation($lg);
        }
        $purpose = $service->getPurposesArray();
        $return['services'][$service->label()] = [
          'id' => $service->oid,
          'label' => $service->getLabel(),
          'name' => $service->getName(),
          'description' => $service->getDescription(),
          'required' => $service->getRequired(),
          'cookies' => $service->getCookiesArray(),
          'scripts' => $service->getScriptsArray(),
          'default' => $service->getDefault(),
          'purposes' => $purpose,
        ];
        $purposes = array_merge($purposes, $purpose);
      }
      $return['purposes'] = array_unique($purposes);
    }
    return $return;
  }

  /**
   * Get all services Orejime.
   *
   * @return array
   *   List of services orejimes.
   */
  public function getAllServices() {
    $query = $this->entityTypeManager->getStorage('orejime_service')->getQuery();
    $query->accessCheck();
    $query->condition('status', TRUE, '=');
    return $query->execute();
  }

  /**
   * Get scripts.
   *
   * @return array|mixed
   *   List of scripts.
   */
  public function getAllScripts() {
    $scripts = [];
    $services = $this->getServicesManage();
    foreach ($services['services'] as $id => $service) {
      foreach ($service['scripts'] as $script_service) {
        $scripts[] = ['type' => $id, 'script' => $script_service];
      }
    }
    return $scripts;
  }

  /**
   * Set opt-in to js.
   *
   * @param array $javascript
   *   File name of javascript.
   */
  public function setOptIn(array &$javascript) {
    $scripts = $this->getAllScripts();
    $keys = array_keys($javascript);
    foreach ($scripts as $script) {
      $value = preg_grep('/' . preg_quote($script['script'] . '/'), $keys);
      if ($value !== FALSE && count($value) > 0) {
        $value = reset($value);
        $javascript[$value]['preprocess'] = FALSE;
        $javascript[$value]['attributes'] = [
          'data-src' => $value,
          'type' => 'opt-in',
          'data-name' => $script['type'],
          'data-type' => 'application/javascript',
        ];
      }
    }
  }

}
