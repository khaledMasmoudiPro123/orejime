<?php

namespace Drupal\orejime\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Orejime entity.
 *
 * @ingroup orejime
 *
 * @ContentEntityType(
 *   id = "orejime_service",
 *   label = @Translation("Orejime"),
 *   handlers = {
 *     "storage" = "Drupal\orejime\OrejimeStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\orejime\OrejimeListBuilder",
 *     "views_data" = "Drupal\orejime\Entity\OrejimeViewsData",
 *     "translation" = "Drupal\orejime\OrejimeTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\orejime\Form\OrejimeForm",
 *       "add" = "Drupal\orejime\Form\OrejimeForm",
 *       "edit" = "Drupal\orejime\Form\OrejimeForm",
 *       "delete" = "Drupal\orejime\Form\OrejimeDeleteForm",
 *     },
 *     "access" = "Drupal\orejime\OrejimeAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\orejime\OrejimeHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "orejime_service",
 *   data_table = "orejime_service_field_data",
 *   revision_table = "orejime_service_revision",
 *   revision_data_table = "orejime_service_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer orejime entities",
 *   entity_keys = {
 *     "id" = "oid",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log"
 *   },
 *   links = {
 *     "canonical" = "/admin/content/orejime_service/{orejime_service}",
 *     "add-form" = "/admin/content/orejime_service/add",
 *     "edit-form" = "/admin/content/orejime_service/{orejime_service}/edit",
 *     "delete-form" = "/admin/content/orejime_service/{orejime_service}/delete",
 *     "version-history" = "/admin/content/orejime_service/{orejime_service}/revisions",
 *     "revision" = "/admin/content/orejime_service/{orejime_service}/revisions/{orejime_service_revision}/view",
 *     "revision_revert" = "/admin/content/orejime_service/{orejime_service}/revisions/{orejime_service_revision}/revert",
 *     "revision_delete" = "/admin/content/orejime_service/{orejime_service}/revisions/{orejime_service_revision}/delete",
 *     "translation_revert" = "/admin/content/orejime_service/{orejime_service}/revisions/{orejime_service_revision}/revert/{langcode}",
 *     "collection" = "/admin/content/orejime_service",
 *   },
 *   field_ui_base_route = "orejime_service.settings"
 * )
 */
class Orejime extends RevisionableContentEntityBase implements OrejimeInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly,
    // make the orejime_service owner the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->get('label')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCookies() {
    return $this->get('cookies')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCookiesArray() {
    $text = $this->getCookies();
    $gas = \Drupal::config('orejime.settings')->get('analytics');
    $gas = $gas && $gas !== '' ? explode(',', $gas) : [];
    $text = $text ? explode("\r\n", $text) : [];
    $return = [];
    foreach ($text as $line) {
      if (strpos($line, '{ga}') !== FALSE) {
        foreach ($gas as $ga) {
          $line_ga = str_replace('{ga}', $ga, $line);
          $line_array = explode("|", $line_ga);
          $return[] = count($line_array) > 1 ? $line_array : $line_ga;
        }
      }
      else {
        $line_array = explode("|", $line);
        $return[] = count($line_array) > 1 ? $line_array : $line;
      }
    }
    return $return;
  }

  /**
   * Get description.
   *
   * @return string
   *   The description of this service.
   */
  public function getDescription() {
    return $this->get('description')->value;
  }

  /**
   * Get default.
   *
   * @return bool
   *   If this service is default.
   */
  public function getDefault() {
    return $this->get('default')->value;
  }

  /**
   * Get scripts.
   *
   * @return string
   *   Get scripts in a string.
   */
  public function getScripts() {
    return $this->get('scripts')->value;
  }

  /**
   * Get scripts in an array.
   *
   * @return array
   *   List of scripts.
   */
  public function getScriptsArray() {
    $scripts = $this->getScripts();
    return $scripts ? explode("\r\n", $scripts) : [];
  }

  /**
   * Get required.
   *
   * @return bool
   *   TRUE if required FALSE it not required.
   */
  public function getRequired() {
    return $this->get('required')->value;
  }

  /**
   * Get purposes.
   *
   * @return string
   *   The list of purposes in a string.
   */
  public function getPurposes() {
    return $this->get('purposes')->value;
  }

  /**
   * Get purposes in an array.
   *
   * @return array
   *   The list of purposes in an array.
   */
  public function getPurposesArray() {
    $purposes = $this->getPurposes();
    return $purposes ? explode(',', $purposes) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Orejime entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('System Name'))
      ->setDescription(t('The System name of the Orejime entity.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Orejime is published.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 50,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * Get status.
   *
   * @return bool
   *   True if actived
   */
  public function status() {
    return !empty($this->status);
  }

}
