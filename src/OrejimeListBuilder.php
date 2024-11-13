<?php

namespace Drupal\orejime;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of Orejime entities.
 *
 * @ingroup orejime
 */
class OrejimeListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['label'] = $this->t('Label');
    $header['description'] = $this->t('Description');
    $header['purposes'] = $this->t('Purposes');
    $header['cookies'] = $this->t('Cookies');
    $header['scripts'] = $this->t('Scripts');
    $header['required'] = $this->t('Required');
    $header['default'] = $this->t('Enabled by default');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\orejime\Entity\Orejime $entity */
    $row['name'] = $entity->label();
    $row['label'] = $entity->getLabel();
    $row['description'] = $entity->getDescription();
    $row['purposes'] = $entity->getPurposes();
    $row['cookies'] = $entity->getCookies();
    $row['scripts'] = $entity->getScripts();
    $row['required'] = $entity->getRequired();
    $row['default'] = $entity->getDefault();
    $row['status'] = $entity->status();
    return $row + parent::buildRow($entity);
  }

}
