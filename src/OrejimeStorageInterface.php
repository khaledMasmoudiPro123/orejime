<?php

namespace Drupal\orejime;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface OrejimeStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Orejime revision IDs for a specific Orejime.
   *
   * @param \Drupal\orejime\Entity\OrejimeInterface $entity
   *   The Orejime entity.
   *
   * @return int[]
   *   Orejime revision IDs (in ascending order).
   */
  public function revisionIds(OrejimeInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Orejime author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Orejime revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\orejime\Entity\OrejimeInterface $entity
   *   The Orejime entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(OrejimeInterface $entity);

  /**
   * Unsets the language for all Orejime with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
