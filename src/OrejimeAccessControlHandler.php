<?php

namespace Drupal\orejime;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Orejime entity.
 *
 * @see \Drupal\orejime\Entity\Orejime.
 */
class OrejimeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\orejime\Entity\OrejimeInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished orejime entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published orejime entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit orejime entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete orejime entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add orejime entities');
  }

}
