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
   */
  protected $moduleHandler;

  /**
   * Constructs a \Drupal\user\StatisticsSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);

    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

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
    $form['bootstrap'] = array(
      '#type' => 'vertical_tabs',
    );
    $form['minimized'] = array(
      '#type' => 'fieldset',
      '#title' => t('Libraries form'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#group' => 'bootstrap'
    );
    $form['minimized']['minimized_options'] = array(
      '#type' => 'radios',
      '#title' => t('Choose minimized or not libraries.'),
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
      '#group' => 'bootstrap'
    );
    $form['theme']['visibility'] = array(
      '#type' => 'radios',
      '#title' => t('Activate on specific themes'),
      '#options' => array(
        0 => t('All themes except those listed'),
        1 => t('Only the listed themes'),
      ),
      '#default_value' => $config->get('theme.visibility'),
    );
    $form['theme']['themes'] = array(
      '#type' => 'select',
      '#title' => 'List of themes where library will be loaded.',
      '#options' => $active_themes,
      '#multiple' => TRUE,
      '#default_value' => $config->get('theme.themes'),
      '#description' => t("Specify in which themes you wish the library to load."),
    );

    // Files settings.
    $form['files'] = array(
      '#type' => 'fieldset',
      '#title' => t('Files Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#group' => 'bootstrap'
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
      ->set('theme.visibility', $form_state->getValue('visibility'))
      ->set('theme.themes', $form_state->getValue('themes'))
      ->set('minimized.options', $form_state->getValue('minimized_options'))
      ->set('files.types', $form_state->getValue('types'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
?>