<?php

namespace Drupal\orejime;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\orejime\Entity\OrejimeInterface;

/**
 * Defines the storage handler class for Orejime entities.
 *
 * This extends the base storage class, adding required special handling for
 * Orejime entities.
 *
 * @ingroup orejime
 */
class OrejimeStorage extends SqlContentEntityStorage implements OrejimeStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(OrejimeInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {orejime_service_revision} WHERE oid=:oid ORDER BY vid',
      [':oid' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {orejime_service_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(OrejimeInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {orejime_service_field_revision} WHERE oid = :oid AND default_langcode = 1', [':oid' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('orejime_service_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
