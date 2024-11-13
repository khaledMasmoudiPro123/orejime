<?php

namespace Drupal\orejime\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Asset\CssOptimizer;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Settings form for module Orejime.
 *
 * @ingroup orejime
 */
class OrejimeSettingsForm extends ConfigFormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Defaults colors.
   *
   * @var string[]
   */
  protected $defaultColors = [
    'primary' => '#057eb6',
    'secondary' => '#008a28',
    'tertiary' => '#666',
    'text' => '#eee',
    'background' => '#333',
    'light' => '#aaa',
    'border' => '#555',
  ];

  /**
   * Request Path.
   *
   * @var \Drupal\system\Plugin\Condition\RequestPath
   */
  protected $condition;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Stores the state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Creates a new OrejimeSettingsForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Component\Plugin\Factory\FactoryInterface $plugin_factory
   *   The condition plugin factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   Time.
   * @param \Drupal\Core\State\StateInterface $state
   *   The State service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function __construct(ConfigFactoryInterface $config_factory, FactoryInterface $plugin_factory, ModuleHandlerInterface $module_handler, FileSystemInterface $file_system, TimeInterface $time, StateInterface $state, EntityTypeManagerInterface $entityTypeManager, ModuleExtensionList $extension_list_module, FileUrlGeneratorInterface $file_url_generator) {
    parent::__construct($config_factory);
    $this->condition = $plugin_factory->createInstance('request_path');
    $this->moduleHandler = $module_handler;
    $this->fileSystem = $file_system;
    $this->time = $time;
    $this->state = $state;
    $this->entityTypeManager = $entityTypeManager;
    $this->moduleExtensionList = $extension_list_module;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.condition'),
      $container->get('module_handler'),
      $container->get('file_system'),
      $container->get('datetime.time'),
      $container->get('state'),
      $container->get('entity_type.manager'),
      $container->get('extension.list.module'),
      $container->get('file_url_generator')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'orejime_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['orejime.settings'];
  }

  /**
   * Defines the settings form for Orejime entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['orejime_settings'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Settings'),
    ];
    $form['account'] = [
      '#type' => 'details',
      '#group' => 'orejime_settings',
      '#title' => $this->t('General settings'),
    ];
    $form['ignore'] = [
      '#type' => 'details',
      '#group' => 'orejime_settings',
      '#title' => $this->t('Ignore settings'),
    ];
    $form['language'] = [
      '#type' => 'details',
      '#group' => 'orejime_settings',
      '#title' => $this->t('Language settings'),
      '#tree' => TRUE,
    ];
    $form['color'] = [
      '#type' => 'details',
      '#group' => 'orejime_settings',
      '#title' => $this->t('Colors'),
      '#attributes' => ['class' => ['color-form']],
      '#attached' => [
        'library' => ['orejime/orejime.color'],
        'drupalSettings' => [
          'color' => $this->getSettings('color'),
        ],
      ],
    ];
    $form['categories'] = [
      '#type' => 'details',
      '#group' => 'orejime_settings',
      '#title' => $this->t('Categories'),
    ];

    $form['account']['iframe_consent'] = [
      '#title' => $this->t('Enable JS Iframe consent.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSettings('iframe_consent', 'orejime'),
      '#description' => $this->t('Allow to use iframe-consent tag for video player with attributes src for video embed and poster for thumbnail.'),
    ];
    $form['account']['cookie_name'] = [
      '#title' => $this->t('Cookie Name'),
      '#type' => 'textfield',
      '#default_value' => $this->getSettings('cookie_name', 'orejime'),
      '#required' => TRUE,
      '#description' => $this->t('You can customize the name of the cookie that Orejime uses for storing user consent decisions.'),
    ];
    $form['account']['expires_after_days'] = [
      '#title' => $this->t('Cookie expires after days'),
      '#type' => 'textfield',
      '#default_value' => $this->getSettings('expires_after_days', '365'),
      '#required' => TRUE,
      '#description' => $this->t('You can set a custom expiration time for the Orejime cookie, in days.'),
    ];
    $form['account']['cookie_domain'] = [
      '#title' => $this->t('Domain'),
      '#type' => 'textfield',
      '#default_value' => $this->getSettings('cookie_domain', ''),
      '#required' => FALSE,
      '#description' => $this->t('You can provide a custom domain for the Orejime cookie, for example to make it available on every associated subdomains.'),
    ];
    $form['account']['privacy_policy'] = [
      '#title' => $this->t('Privacy Policy'),
      '#type' => 'textfield',
      '#default_value' => $this->getSettings('privacy_policy', 'orejime'),
      '#required' => TRUE,
      '#description' => $this->t('You must provide a link to your privacy policy page'),
    ];
    $form['account']['logo'] = [
      '#title' => $this->t('URL logo notice'),
      '#type' => 'textfield',
      '#default_value' => $this->getSettings('logo'),
      '#required' => FALSE,
      '#description' => $this->t('Optional. You can pass an image url to show in the notice.'),
    ];
    $form['account']['must_consent'] = [
      '#title' => $this->t('Must consent'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSettings('must_consent', FALSE),
      '#required' => FALSE,
      '#description' => $this->t('"mustConsent" is set to true, Orejime will directly display the consent manager modal and not allow the user to close it before having actively consented or declined the use of third-party apps.'),
    ];
    $form['account']['must_notice'] = [
      '#title' => $this->t('Must notice'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSettings('must_notice', FALSE),
      '#required' => FALSE,
      '#description' => $this->t('If "mustNotice" is set to true, Orejime will display the consent notice and not allow the user to close it before having actively consented or declined the use of third-party apps. Has no effect if mustConsent is set to true.'),
    ];
    $form['account']['analytics'] = [
      '#title' => $this->t('UA codes to manage'),
      '#type' => 'textfield',
      '#default_value' => $this->getSettings('analytics'),
      '#required' => FALSE,
      '#description' => $this->t('Liste here UA used in the website.'),
    ];
    $form['account']['orejime_css'] = [
      '#title' => $this->t('CSS Orejime'),
      '#type' => 'textfield',
      '#default_value' => $this->getSettings('orejime_css'),
      '#required' => FALSE,
      '#description' => $this->t('Set here the url if you want a different CSS or use external link'),
    ];
    $form['account']['orejime_js'] = [
      '#title' => $this->t('JS Orejime'),
      '#type' => 'textfield',
      '#default_value' => $this->getSettings('orejime_js'),
      '#required' => FALSE,
      '#description' => $this->t('Set here the url if you want a different js for orejime or use external link'),
    ];
    $form['account']['debug'] = [
      '#title' => $this->t('Debug Mode'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSettings('debug'),
      '#required' => FALSE,
      '#description' => $this->t('Set Orejime in debug mode to have a few stuff logged in the console, like warning about missing translations.'),
    ];
    $data = $this->getSettings('request_path');
    if ($data) {
      $this->condition->setConfiguration($data);
    }
    else {
      $data = [];
    }
    $form['ignore'] += $this->condition->buildConfigurationForm($form['ignore'], $form_state);
    $form['language']['texts'] = [
      '#title' => $this->t('Texts of Orejime'),
      '#type' => 'textarea',
      '#rows' => 35,
      '#default_value' => $this->getSettings('texts'),
      '#required' => FALSE,
      '#description' => $this->t('Translation file. Example here : https://raw.githubusercontent.com/empreinte-digitale/orejime/master/src/translations/en.yml'),
    ];
    $form['color']['color_enable'] = [
      '#title' => $this->t('Enable custom CSS'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSettings('color.enable', 0),
      '#description' => $this->t('Allow to get a custom color.'),
      '#required' => FALSE,
    ];
    $state = [
      'visible' => [
        'input[name="color_enable"]' => ['checked' => TRUE],
      ],
    ];
    $form['color']['container_zone'] = [
      '#type' => 'container',
      '#states' => $state,
    ];
    $form['color']['container_zone']['zone'] = [
      '#markup' => '<span id="color-zone"></span>',
    ];
    $color_class = ['class' => ['js-color-palette']];
    $form['color']['color_background'] = [
      '#title' => $this->t('Background Color'),
      '#type' => 'color',
      '#default_value' => $this->getSettings('color.background', $this->defaultColors['background']),
      '#required' => FALSE,
      '#states' => $state,
      '#attributes' => $color_class,
    ];
    $form['color']['color_text'] = [
      '#title' => $this->t('Text color'),
      '#type' => 'color',
      '#default_value' => $this->getSettings('color.text', $this->defaultColors['text']),
      '#required' => FALSE,
      '#states' => $state,
      '#attributes' => $color_class,
    ];
    $form['color']['color_light'] = [
      '#title' => $this->t('Light color'),
      '#type' => 'color',
      '#default_value' => $this->getSettings('color.light', $this->defaultColors['light']),
      '#required' => FALSE,
      '#description' => $this->t('Porposes and powered.'),
      '#states' => $state,
      '#attributes' => $color_class,
    ];
    $form['color']['color_primary'] = [
      '#title' => $this->t('Primary color'),
      '#type' => 'color',
      '#default_value' => $this->getSettings('color.primary', $this->defaultColors['primary']),
      '#required' => FALSE,
      '#description' => $this->t('Button Learn more, accept all, decline All, Enable.'),
      '#states' => $state,
      '#attributes' => $color_class,
    ];
    $form['color']['color_secondary'] = [
      '#title' => $this->t('Secondary color'),
      '#type' => 'color',
      '#default_value' => $this->getSettings('color.secondary', $this->defaultColors['secondary']),
      '#required' => FALSE,
      '#description' => $this->t('Button accept and save.'),
      '#states' => $state,
      '#attributes' => $color_class,
    ];
    $form['color']['color_tertiary'] = [
      '#title' => $this->t('Tertiary color'),
      '#type' => 'color',
      '#default_value' => $this->getSettings('color.tertiary', $this->defaultColors['tertiary']),
      '#required' => FALSE,
      '#description' => $this->t('Button decline.'),
      '#states' => $state,
      '#attributes' => $color_class,
    ];
    $form['color']['color_border'] = [
      '#title' => $this->t('Border color'),
      '#type' => 'color',
      '#default_value' => $this->getSettings('color.border', $this->defaultColors['border']),
      '#required' => FALSE,
      '#states' => $state,
      '#attributes' => $color_class,
    ];
    $form['color']['color_btn_radius'] = [
      '#title' => $this->t('Button radius'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSettings('color.btn_radius', 1),
      '#description' => $this->t('Allow to get a custom color.'),
      '#required' => FALSE,
      '#states' => $state,
    ];

    // $this->config('orejime.settings')->clear('categories')->save();
    $query = $this->entityTypeManager->getStorage('orejime_service')->getQuery();
    $query->accessCheck();
    $query->condition('status', TRUE, '=');
    $result = $query->execute();
    if (!empty($result)) {
      $services = $this->entityTypeManager->getStorage('orejime_service')->loadMultiple($result);
      foreach ($services as $service) {
        $services_options[$service->label()] = $service->label();
      }
    }
    $form['categories']['list_categories'] = [
      '#type' => 'table',
      '#prefix' => '<div id="replace-this-link">',
      '#suffix' => '</div>',
      '#caption' => $this->t('Orejime categories'),
      '#header' => [
        $this->t('Name'),
        $this->t('Title'),
        $this->t('Description'),
        $this->t('Apps'),
        $this->t('Weight'),
      ],
      '#empty' => 'None',
      '#tableselect' => FALSE,
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'group-order-weight',
        ],
      ],
    ];
    $nb_cat = $form_state->get('nb_cat');
    if ($nb_cat === NULL) {
      $nb_cat = 1;
      $form_state->set('nb_cat', 1);

    }
    $data['categories'] = $this->config('orejime.settings')->get('categories') ?? [];
    array_multisort(array_column($data['categories'], 'weight'), SORT_ASC, $data['categories']);
    if (isset($data['categories']) && is_array($data['categories'])) {
      $k = 1;
      foreach ($data['categories'] as $key => $item) {
        $form['categories']['list_categories'][$k] = [
          '#attributes' => ['class' => ['draggable']],
          '#weight' => $k,
        ];
        $form['categories']['list_categories'][$k]['name'] = [
          '#type' => 'textfield',
          '#size' => 20,
          '#required' => FALSE,
          '#default_value' => $this->getSettings('categories.' . $key . '.name'),
        ];
        $form['categories']['list_categories'][$k]['title'] = [
          '#type' => 'textfield',
          '#size' => 20,
          '#required' => FALSE,
          '#default_value' => $this->getSettings('categories.' . $key . '.title'),
        ];
        $form['categories']['list_categories'][$k]['description'] = [
          '#title' => $this->t('Description'),
          '#type' => 'textarea',
          '#rows' => 3,
          '#required' => FALSE,
          '#default_value' => $this->getSettings('categories.' . $key . '.description'),
        ];
        $form['categories']['list_categories'][$k]['apps'] = [
          '#type' => 'checkboxes',
          '#options' => $services_options,
          '#title' => $this->t('Services'),
          '#required' => FALSE,
          '#default_value' => $this->getSettings('categories.' . $key . '.apps'),
        ];
        $form['categories']['list_categories'][$k]['weight'] = [
          '#type' => 'weight',
          '#title' => $this->t('Weight'),
          '#title_display' => 'invisible',
          '#default_value' => $k,
          '#attributes' => ['class' => ['group-order-weight']],
        ];
        $k = $k + 1;
      }
    }
    else {
      $k = 1;
    }
    for ($i = $k; $i < $k + $nb_cat; $i++) {
      $form['categories']['list_categories'][$i] = [
        '#attributes' => ['class' => ['draggable']],
        '#weight' => $k,
      ];
      $form['categories']['list_categories'][$i]['name'] = [
        '#type' => 'textfield',
        '#size' => 20,
        '#required' => FALSE,
      ];
      $form['categories']['list_categories'][$i]['title'] = [
        '#type' => 'textfield',
        '#size' => 20,
        '#required' => FALSE,
      ];
      $form['categories']['list_categories'][$i]['description'] = [
        '#type' => 'textarea',
        '#rows' => 3,
        '#required' => FALSE,
      ];
      $form['categories']['list_categories'][$i]['apps'] = [
        '#type' => 'checkboxes',
        '#options' => $services_options,
        '#required' => FALSE,
      ];
      $form['categories']['list_categories'][$i]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight'),
        '#title_display' => 'invisible',
        '#default_value' => $i,
        '#attributes' => ['class' => ['group-order-weight']],
      ];
    }

    $form['categories']['add_cat'] = [
      '#type' => 'submit',
      '#submit' => ['::addCategory'],
      '#value' => $this->t('Add a category'),
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'effect' => 'fade',
        'wrapper' => 'replace-this-link',
      ],
    ];

    if ($this->moduleHandler->moduleExists('content_translation')) {
      $form_state->set(['content_translation', 'key'], 'language');
      $form['language'] += content_translation_enable_widget('orejime_service', 'orejime_service', $form, $form_state);
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * Ajax add callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   List categories updated.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) : array {
    return $form['categories']['list_categories'];
  }

  /**
   * Ajax add column.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function addCategory(array &$form, FormStateInterface $form_state) {
    $nb_cats = $form_state->get('nb_cat');
    $form_state->set('nb_cat', $nb_cats + 1);
    $form_state->setRebuild();
  }

  /**
   * Implements_form_validate().
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    try {
      Yaml::parse($form_state->getValue(['language', 'texts']));
    }
    catch (\Exception $e) {
      $form_state->setErrorByName('language][texts', $e->getMessage());
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * Allow to get settings or default value.
   *
   * @param string $name
   *   The name of settings.
   * @param string $default
   *   Give a default value.
   *
   * @return string|array
   *   The value of the settings or the default value if empty.
   */
  public function getSettings(string $name, string $default = '') {
    $site_config = $this->config('orejime.settings');
    return $site_config->get($name) ?? $default;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->condition->submitConfigurationForm($form, $form_state);
    $this->config('orejime.settings')
      ->set('iframe_consent', $form_state->getValue('iframe_consent'))
      ->set('cookie_name', $form_state->getValue('cookie_name'))
      ->set('expires_after_days', $form_state->getValue('expires_after_days'))
      ->set('cookie_domain', $form_state->getValue('cookie_domain'))
      ->set('logo', $form_state->getValue('logo'))
      ->set('privacy_policy', $form_state->getValue('privacy_policy'))
      ->set('must_consent', $form_state->getValue('must_consent'))
      ->set('must_notice', $form_state->getValue('must_notice'))
      ->set('implicit_consent', $form_state->getValue('implicit_consent'))
      ->set('analytics', $form_state->getValue('analytics'))
      ->set('orejime_css', $form_state->getValue('orejime_css'))
      ->set('orejime_js', $form_state->getValue('orejime_js'))
      ->set('texts', $form_state->getValue(['language', 'texts']))
      ->set('debug', $form_state->getValue('debug'))
      ->set('color.enable', $form_state->getValue('color_enable'))
      ->set('color.background', $form_state->getValue('color_background'))
      ->set('color.text', $form_state->getValue('color_text'))
      ->set('color.light', $form_state->getValue('color_light'))
      ->set('color.primary', $form_state->getValue('color_primary'))
      ->set('color.secondary', $form_state->getValue('color_secondary'))
      ->set('color.tertiary', $form_state->getValue('color_tertiary'))
      ->set('color.btn_radius', $form_state->getValue('color_btn_radius'))
      ->set('color.border', $form_state->getValue('color_border'))
      ->set('request_path', $this->condition->getConfiguration())
      ->save();

    $values = $form_state->getValue('list_categories');
    $list_cats = [];
    foreach ($values as $cat) {
      if ($cat['name'] or $cat['title'] or $cat['description'] or $cat['apps']) {
        if ($cat['name'] != '') {
          $list_cats[$cat['name']] = $cat;
        }
      }
    }

    $this->config('orejime.settings')->set('categories', $list_cats)->save();

    $this->changeStyleSheet($form_state->getValues());
    parent::submitForm($form, $form_state);
  }

  /**
   * Change the stylesheet of Orejime.
   *
   * @param array $values
   *   Stylesheet values.
   */
  private function changeStyleSheet(array $values) {
    // Load all values for the change.
    $border_radius = $values['color_btn_radius'] ? 4 : 0;
    $new_colors = [];
    foreach ($this->defaultColors as $def => $val) {
      $new_colors[$def] = $values['color_' . $def] ?: $val;
    }

    // Delete the old file.
    $file = $this->config('orejime.settings')->get('color.url_css');
    if (isset($file)) {
      @$this->fileSystem->unlink($file);
    }
    if (isset($file) && $file = dirname($file)) {
      @$this->fileSystem->rmdir($file);
    }

    if ($values['color_enable'] === 1) {
      // Prepare the new file.
      $target = 'public://orejime';
      $this->fileSystem->prepareDirectory($target, FileSystemInterface::CREATE_DIRECTORY);
      $target = $target . '/';
      $source = $this->moduleExtensionList->getPath('orejime') . '/css/orejime_drupal.css';

      // Transform default file to the new file.
      if (file_exists($source)) {
        $css_optimizer = new CssOptimizer($this->fileUrlGenerator);
        $style = $css_optimizer->loadFile($source, FALSE);
        $base = base_path() . dirname($source) . '/';
        $css_optimizer->rewriteFileURIBasePath = dirname($base);
        $style = preg_replace_callback('/url\([\'"]?(?![a-z]+:|\/+)([^\'")]+)[\'"]?\)/i', [
          $css_optimizer,
          'rewriteFileURI',
        ], $style);
        $style = $this->rewriteStylesheet($new_colors, $style, $border_radius);
        $base_file = $this->fileSystem->basename($source);
        $css = $target . $base_file;
        $this->fileSystem->saveData($style, $css, FileExists::Rename);
        $this->fileSystem->chmod($css);
        $this->config('orejime.settings')->set('color.url_css', $css);
        $this->config('orejime.settings')->save();
        $this->state->set('system.css_js_query_string', base_convert($this->time->getCurrentTime(), 10, 36));
      }
    }
  }

  /**
   * Rewrite css.
   *
   * @param array $color_list
   *   Color List.
   * @param string $style
   *   Stylesheet.
   * @param string $border_radius
   *   Border radius or not.
   *
   * @return string
   *   Return new stylesheet.
   */
  private function rewriteStylesheet(array $color_list, string $style, string $border_radius) : string {
    $style = str_replace('border-radius: 4px !important', "border-radius: $border_radius !important;", $style);
    $style = preg_split('/(#[0-9a-f]{6}|#[0-9a-f]{3})/i', $style, -1, PREG_SPLIT_DELIM_CAPTURE);
    $is_color = FALSE;
    $output = '';
    // Iterate over all the parts.
    foreach ($style as $chunk) {
      if ($is_color) {
        $chunk = strtolower($chunk);
        if ($key = array_search($chunk, $this->defaultColors)) {
          $chunk = $color_list[$key];
        }
      }
      $output .= $chunk;
      $is_color = !$is_color;
    }
    if (isset($fixed)) {
      $output .= $fixed;
    }
    return $output;
  }

}
