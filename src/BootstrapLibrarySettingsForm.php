<?php
/**
 * @file
 * Contains \Drupal\bootstrap_library\BootstrapLibrarySettingsForm
 */
namespace Drupal\bootstrap_library;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure bootstrap_library settings for this site.
 */
class BootstrapLibrarySettingsForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface

  protected $moduleHandler;
   */
  /**
   * Constructs a \Drupal\user\StatisticsSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.

  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);

    $this->moduleHandler = $module_handler;
  }
   */
  /**
   * {@inheritdoc}

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }
   */
  /** 
   * {@inheritdoc}
   */

  public function getFormId() {
    return 'bootstrap_library_admin_settings';
  }
  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'bootstrap_library.settings',
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bootstrap_library.settings');
	
    $themes = \Drupal::service('theme_handler')->listInfo();
    $active_themes = array();
    foreach ($themes as $key => $theme) {
      if ($theme->status) {
        $active_themes[$key] = $theme->info['name'];
      }
    }
    // Load from CDN
    $form['cdn'] = array(
      '#type' => 'fieldset',
      '#title' => t('Load Boostrap from CDN'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
	$data = _bootstrap_library_cdn_versions();
    $cdn_options =  json_decode( $data );
    $versions = array_keys(_bootstrap_library_object_to_array($cdn_options->bootstrap));
    $options = array_combine($versions, $versions);
    array_unshift( $options, 'Load locally' );
    $form['cdn']['bootstrap'] = array(
      '#type' => 'select',
      '#title' => t('Select Bootstrap version to load via CDN, non for local library'),
      '#options' => $options,
      '#default_value' => $config->get('cdn.bootstrap'),
    );
    $form['cdn']['cdn_options'] = array(
      '#type' => 'hidden', 
	  '#value' => $data
	);
	// Production or minimized version
    $form['minimized'] = array(
      '#type' => 'fieldset',
      '#title' => t('Production or development version'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['minimized']['minimized_options'] = array(
      '#type' => 'radios',
      '#title' => t('Choose minimized or non minimized version.'),
      '#options' => array(
        0 => t('Use non minimized libraries (Development)'),
        1 => t('Use minimized libraries (Production)'),
      ),
      '#default_value' => $config->get('minimized.options'),
    );
    // Per-theme visibility.
    $form['theme'] = array(
      '#type' => 'fieldset',
      '#title' => t('Themes Visibility'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['theme']['theme_visibility'] = array(
      '#type' => 'radios',
      '#title' => t('Activate on specific themes'),
      '#options' => array(
        0 => t('All themes except those listed'),
        1 => t('Only the listed themes'),
      ),
      '#default_value' => $config->get('theme.visibility'),
    );
    $form['theme']['theme_themes'] = array(
      '#type' => 'select',
      '#title' => 'List of themes where library will be loaded.',
      '#options' => $active_themes,
      '#multiple' => TRUE,
      '#default_value' => $config->get('theme.themes'),
      '#description' => t("Specify in which themes you wish the library to load."),
    );
    // Per-path visibility.
    $form['url'] = array(
      '#type' => 'fieldset',
      '#title' => t('Activate on specific URLs'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['url']['url_visibility'] = array(
      '#type' => 'radios',
      '#title' => t('Load bootstrap on specific pages'),
      '#options' => array(0 => t('All pages except those listed'), 1 => t('Only the listed pages')),
      '#default_value' => $config->get('url.visibility'),
    );
    $form['url']['url_pages'] = array(
      '#type' => 'textarea',
      '#title' => '<span class="element-invisible">' . t('Pages') . '</span>',
      '#default_value' => _bootstrap_library_array_to_string($config->get('url.pages')),
      '#description' => t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", array('%blog' => 'blog', '%blog-wildcard' => 'blog/*', '%front' => '<front>')),
    );

    // Files settings.
    $form['files'] = array(
      '#type' => 'fieldset',
      '#title' => t('Files Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['files']['types'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Select which files to load from the library. By default you should check both, but in some cases you will need to load only CSS or JS Bootstrap files.'),
      '#options' => array(
        'css' => t('CSS files'),
        'js' => t('Javascript files'),
      ),
      '#default_value' => $config->get('files.types'),
    );

    return parent::buildForm($form, $form_state);
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('bootstrap_library.settings')
      ->set('theme.visibility', $form_state->getValue('theme_visibility'))
      ->set('theme.themes', $form_state->getValue('theme_themes'))
      ->set('url.visibility', $form_state->getValue('url_visibility'))
      ->set('url.pages', _bootstrap_library_string_to_array($form_state->getValue('url_pages')))
      ->set('minimized.options', $form_state->getValue('minimized_options'))
      ->set('cdn.bootstrap', $form_state->getValue('bootstrap'))
      ->set('cdn.options', $form_state->getValue('cdn_options'))
      ->set('files.types', $form_state->getValue('types'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}

/**
 * Load CDN version optios.
 */
function _bootstrap_library_cdn_versions() {
  static $uri = 'http://netdna.bootstrapcdn.com/data/bootstrapcdn.json';
  $json_response = NULL;
  try {
    $client = \Drupal::httpClient();
    $response = $client->get($uri, array('headers' => array('Accept' => 'text/plain')));
	$data = (string) $response->getBody();
    if (empty($data)) {
      return FALSE;
    }
  }
  catch (RequestException $e) {
    watchdog_exception('bootstrap_library', $e->getMessage());
    return FALSE;
  }
  return $data;
}

?>