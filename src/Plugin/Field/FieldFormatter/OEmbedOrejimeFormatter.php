<?php

namespace Drupal\orejime\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\media\Entity\MediaType;
use Drupal\media\IFrameUrlHelper;
use Drupal\media\OEmbed\Resource;
use Drupal\media\OEmbed\ResourceException;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\media\Plugin\media\Source\OEmbedInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;


/**
 * Orejime Oembed.
 *
 * @internal
 *   This is an internal part of the oEmbed system and should only be used by
 *   oEmbed-related code in Drupal core.
 *
 * @FieldFormatter(
 *   id = "oembed_orejime",
 *   label = @Translation("OEmbed Orejime : Iframe Consent"),
 *   field_types = {
 *     "link",
 *     "string",
 *     "string_long",
 *   },
 * )
 */
class OEmbedOrejimeFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The resource fetcher.
   *
   * @var ResourceFetcherInterface
   */
  protected ResourceFetcherInterface $resourceFetcher;

  /**
   * The url resolver.
   *
   * @var UrlResolverInterface
   */
  protected UrlResolverInterface $urlResolver;

  /**
   * The file url generator.
   *
   * @var FileUrlGeneratorInterface
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * The iframe url helper.
   *
   * @var IFrameUrlHelper
   */
  protected IFrameUrlHelper $iFrameUrlHelper;

  /**
   * The entity field manager.
   *
   * @var EntityFieldManagerInterface
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;


  /**
   * Constructs a OEmbedOrejimeFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, ResourceFetcherInterface $resource_fetcher, UrlResolverInterface $url_resolver, FileUrlGeneratorInterface $file_url_generator, IFrameUrlHelper $iframe_url_helper, EntityFieldManagerInterface $entity_field_manager, AccountInterface $current_user) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
    $this->resourceFetcher = $resource_fetcher;
    $this->urlResolver = $url_resolver;
    $this->fileUrlGenerator = $file_url_generator;
    $this->iFrameUrlHelper = $iframe_url_helper;
    $this->entityFieldManager = $entity_field_manager;
    $this->currentUser = $current_user;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('media.oembed.resource_fetcher'),
      $container->get('media.oembed.url_resolver'),
      $container->get('file_url_generator'),
      $container->get('media.oembed.iframe_url_helper'),
      $container->get('entity_field.manager'),
      $container->get('current_user'),

    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
        'aria_label' => '',
        'image' => 'thumbnail',
        'image_style' => '',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);

    $list_images = ['thumbnail' => 'Thumbnail (default)'];
    $fields = $this->entityFieldManager->getFieldMap();

    foreach ($fields[$this->fieldDefinition->getTargetEntityTypeId()] as $field_name => $field) {
      if (isset($field['bundles'][$this->fieldDefinition->getTargetBundle()])) {
        if ($field['type'] === 'image') {
          $list_images[$field_name] = $field_name;
        }
      }
    }
    $form['image'] = [
      '#type' => 'select',
      '#title' => $this->t('Image'),
      '#options' => $list_images,
      '#default_value' => $this->getSetting('image'),
      '#required' => TRUE,
    ];

    $image_styles = image_style_options(FALSE);
    $description_link = Link::fromTextAndUrl(
      $this->t('Configure Image Styles'),
      Url::fromRoute('entity.image_style.collection')
    );
    $form['image_style'] = [
      '#title' => $this->t('Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
      '#description' => $description_link->toRenderable() + [
          '#access' => $this->currentUser->hasPermission('administer image styles'),
        ],
    ];

    $form['aria_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Aria Label'),
      '#default_value' => $this->getSetting('aria_label'),
      '#required' => FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = parent::settingsSummary();

    $summary['image'] = 'Image from field : ' . $this->getSetting('image');

    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $image_style_setting = $this->getSetting('image_style');
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = $this->t('Image style: @style', ['@style' => $image_styles[$image_style_setting]]);
    }
    else {
      $summary[] = $this->t('Original image');
    }

    if ($this->getSetting('aria_label')) {
      $summary['aria_label'] = 'Aria Label : ' . $this->getSetting('aria_label');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];
    $parent = $items->getParent()->getEntity();
    foreach ($items as $delta => $item) {
      $main_property = $item->getFieldDefinition()
        ->getFieldStorageDefinition()
        ->getMainPropertyName();
      $value = $item->{$main_property};

      if (empty($value)) {
        continue;
      }

      try {
        $resource_url = $this->urlResolver->getResourceUrl($value);
        $resource = $this->resourceFetcher->fetchResource($resource_url);
        $provider = strtolower($resource->getProvider()->getName());
      } catch (ResourceException $e) {
        $this->messenger()->addError($e->getMessage());
        continue;
      }

      $url = Url::fromRoute('media.oembed_iframe', [], [
        'absolute' => TRUE,
        'query' => [
          'url' => $value,
          'max_width' => 780,
          'max_height' => 440,
          'hash' => $this->iFrameUrlHelper->getHash($value, 780, 440),
        ],
      ])->toString();

      $field_image = $this->getSetting('image');
      $img = $this->getImage($parent, $field_image);
      if (!$img and $field_image !== 'thumbnail') {
        $img = $this->getImage($parent, 'thumbnail');
      }

      $value = [
        '#type' => 'html_tag',
        '#tag' => 'iframe-consent',
        '#attributes' => [
          'type' => 'video',
          'provider' => $provider,
          'src' => $url,
          'poster' => $img,
          'title' => $parent->get('name')->getString(),
          'alt' => '',
          'aria-label' => $this->t($this->getSetting('aria_label')),
          'class' => [],
        ],
        '#attached' => [
          'library' => [
            'media/oembed.formatter',
          ],
        ],
      ];
      $element[$delta] = $value;
    }
    return $element;
  }

  /**
   * Get image from field.
   * --------------
   *
   * @param $entity
   * @param $field_name
   *
   * @return ?string
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @author Fabien Gutknecht <fgutknecht@gaya.fr>
   */
  private function getImage($entity, $field_name): ?string {
    $img = NULL;
    if ($entity->hasField($field_name)) {
      $img = $entity->get($field_name);
      if ($img) {
        $img = $img->referencedEntities();
        if (isset($img[0])) {
          $imgUri = $img[0]->getFileUri();
          $is = $this->entityTypeManager->getStorage('image_style')
            ->load($this->getSetting('image_style'));
          $img = $is ? $this->fileUrlGenerator->transformRelative($is->buildUrl($imgUri)) : $this->fileUrlGenerator->generateString($imgUri);
        }
        else {
          $img = NULL;
        }
      }
    }
    return $img;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition): bool {
    $target_bundle = $field_definition->getTargetBundle();

    if (!parent::isApplicable($field_definition) || $field_definition->getTargetEntityTypeId() !== 'media' || !$target_bundle) {
      return FALSE;
    }
    return MediaType::load($target_bundle)
        ->getSource() instanceof OEmbedInterface;
  }


}
