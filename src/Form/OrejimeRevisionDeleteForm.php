<?php

namespace Drupal\orejime\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a Orejime revision.
 *
 * @ingroup orejime
 */
class OrejimeRevisionDeleteForm extends ConfirmFormBase {


  /**
   * The Orejime revision.
   *
   * @var \Drupal\orejime\Entity\OrejimeInterface
   */
  protected $revision;

  /**
   * The Orejime storage.
   *
   * @var \Drupal\orejime\OrejimeStorageInterface
   */
  protected $orejimeStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new OrejimeRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(EntityStorageInterface $entity_storage, Connection $connection) {
    $this->orejimeStorage = $entity_storage;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity_type.manager');
    return new static(
      $entity_manager->getStorage('orejime_service'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'orejime_service_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => \Drupal::service('date.formatter')
        ->format($this->revision->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.orejime_service.version_history', ['orejime_service' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $orejime_service_revision = NULL) {
    $this->revision = $this->orejimeStorage->loadRevision($orejime_service_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->orejimeStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')
      ->notice('Orejime: deleted %title revision %revision.', [
        '%title' => $this->revision->label(),
        '%revision' => $this->revision->getRevisionId(),
      ]);
    $this->messenger()
      ->addMessage($this->t('Revision from %revision-date of Orejime %title has been deleted.', [
        '%revision-date' => \Drupal::service('date.formatter')
          ->format($this->revision->getRevisionCreationTime()),
        '%title' => $this->revision->label(),
      ]));
    $form_state->setRedirect(
      'entity.orejime_service.canonical',
      ['orejime_service' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {orejime_service_field_revision} WHERE id = :id', [':id' => $this->revision->id()])
        ->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.orejime_service.version_history',
        ['orejime_service' => $this->revision->id()]
      );
    }
  }

}
