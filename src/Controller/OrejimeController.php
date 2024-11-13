<?php

namespace Drupal\orejime\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\orejime\Entity\OrejimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class OrejimeController.
 *
 *  Returns responses for Orejime routes.
 */
class OrejimeController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new OrejimeRevisionRevertForm.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * Displays a Orejime  revision.
   *
   * @param int $orejime_service_revision
   *   The Orejime  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($orejime_service_revision) {
    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $orejime_service_storage */
    $orejime_service_storage = $this->entityTypeManager()
      ->getStorage('orejime_service');
    $orejime_service = $orejime_service_storage->loadRevision($orejime_service_revision);
    return $this->entityTypeManager()->getViewBuilder('orejime_service')->view($orejime_service);
  }

  /**
   * Page title callback for a Orejime  revision.
   *
   * @param int $orejime_service_revision
   *   The Orejime  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($orejime_service_revision) {
    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $orejime_service_storage */
    $orejime_service_storage = $this->entityTypeManager()
      ->getStorage('orejime_service');
    $orejime_service = $orejime_service_storage->loadRevision($orejime_service_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $orejime_service->label(),
      '%date' => $this->dateFormatter->format($orejime_service->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Orejime .
   *
   * @param \Drupal\orejime\Entity\OrejimeInterface $orejime_service
   *   A Orejime  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(OrejimeInterface $orejime_service) {
    $account = $this->currentUser();
    $langcode = $orejime_service->language()->getId();
    $langname = $orejime_service->language()->getName();
    $languages = $orejime_service->getTranslationLanguages();
    $has_translations = (count($languages) > 1);

    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $orejime_service_storage */
    $orejime_service_storage = $this->entityTypeManager()
      ->getStorage('orejime_service');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', [
      '@langname' => $langname,
      '%title' => $orejime_service->label(),
    ]) : $this->t('Revisions for %title', ['%title' => $orejime_service->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all orejime revisions") || $account->hasPermission('administer orejime entities')));
    $delete_permission = (($account->hasPermission("delete all orejime revisions") || $account->hasPermission('administer orejime entities')));

    $rows = [];

    $vids = $orejime_service_storage->revisionIds($orejime_service);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\orejime\OrejimeInterface $revision */
      $revision = $orejime_service_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) &&
        $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $orejime_service->getRevisionId()) {
          $link = Link::fromTextAndUrl($date, new Url('entity.orejime_service.revision', [
            'orejime_service' => $orejime_service->id(),
            'orejime_service_revision' => $vid,
          ]));
        }
        else {
          $link = $orejime_service->toLink($date)->toString();
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ? Url::fromRoute('entity.orejime_service.translation_revert',
                [
                  'orejime_service' => $orejime_service->id(),
                  'orejime_service_revision' => $vid,
                  'langcode' => $langcode,
                ]) : Url::fromRoute('entity.orejime_service.revision_revert',
                [
                  'orejime_service' => $orejime_service->id(),
                  'orejime_service_revision' => $vid,
                ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.orejime_service.revision_delete', [
                'orejime_service' => $orejime_service->id(),
                'orejime_service_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['orejime_service_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
