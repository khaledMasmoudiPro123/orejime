<?php

namespace Drupal\orejime\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Orejime entities.
 *
 * @ingroup orejime
 */
interface OrejimeInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Orejime name.
   *
   * @return string
   *   Name of the Orejime.
   */
  public function getName();

  /**
   * Sets the Orejime name.
   *
   * @param string $name
   *   The Orejime name.
   *
   * @return \Drupal\orejime\Entity\OrejimeInterface
   *   The called Orejime entity.
   */
  public function setName($name);

  /**
   * Gets the Orejime creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Orejime.
   */
  public function getCreatedTime();

  /**
   * Sets the Orejime creation timestamp.
   *
   * @param int $timestamp
   *   The Orejime creation timestamp.
   *
   * @return \Drupal\orejime\Entity\OrejimeInterface
   *   The called Orejime entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Orejime published status indicator.
   *
   * Unpublished Orejime are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Orejime is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Orejime.
   *
   * @param bool $published
   *   TRUE to set this Orejime to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\orejime\Entity\OrejimeInterface
   *   The called Orejime entity.
   */
  public function setPublished($published);

  /**
   * Gets the Orejime revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Orejime revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\orejime\Entity\OrejimeInterface
   *   The called Orejime entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Orejime revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Orejime revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\orejime\Entity\OrejimeInterface
   *   The called Orejime entity.
   */
  public function setRevisionUserId($uid);

}
