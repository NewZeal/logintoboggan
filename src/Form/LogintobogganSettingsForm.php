<?php
/**
 * @file
 * Contains \Drupal\logintoboggan\Form\LogintobogganSettingsForm.
 */

namespace Drupal\logintoboggan\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
/**
 * Configure search settings for this site.
 */
class LogintobogganSettingsForm extends ConfigFormBase {

  protected $moduleHandler;

  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandler $module_handler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
  }

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
    return 'logintoboggan_main_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'logintoboggan.settings',
    ];
  }
  /**
   * Gets the roles to display in this form.
   *
   * @return \Drupal\user\RoleInterface[]
   *   An array of role objects.
   */
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

  $config = $this->config('logintoboggan.settings');
  $_disabled = $this->t('Disabled');
  $_enabled = $this->t('Enabled');
  $form['login'] = array(
    '#type' => 'fieldset',
    '#title' => $this->t('Log in'),
  );
  $form['login']['login_with_email'] = array(
    '#type' => 'checkbox',
    '#title' => $this->t('Allow users to login using their e-mail address'),
    '#default_value' => $config->get('login_with_email'),
    '#description' => $this->t('Users will be able to enter EITHER their username OR their e-mail address to log in.'),
  );

  $form['login']['unified_login'] = array(
    '#type' => 'checkbox',
    '#title' => $this->t('Present a unified login/registration page'),
    '#default_value' => $config->get('unified_login'),
    '#description' => $this->t("Use one page for both login and registration instead of Drupal's tabbed login/registration/password pages."),
  );

  $form['registration'] = array(
    '#type' => 'fieldset',
    '#title' => $this->t('Registration'),
  );

  $form['registration']['confirm_email_at_registration'] = array(
    '#type' => 'checkbox',
    '#title' => $this->t('Use two e-mail fields on registration form'),
    '#default_value' => $config->get('confirm_email_at_registration'),
    '#description' => $this->t('User will have to type the same e-mail address into both fields. This helps to confirm that they\'ve typed the correct address.'),
  );

  if (\Drupal::moduleHandler()->moduleExists('help')) {
    $help_text =  $this->t(" More help in writing the e-mail message can be found at <a href=\"@help\">LoginToboggan help</a>.", array('@help' => '/admin/help/logintoboggan'));
  }
  else {
    $help_text = '';
  }
  $form['registration']['user_email_verification'] = array(
    '#type' => 'checkbox',
    '#title' => t('Set password'),
    '#default_value' => !$this->configFactory->get('user.settings')->get('verify_mail'),
    '#description' => $this->t("This will allow users to choose their initial password when registering (note that this setting is a mirror of the <a href=\"@settings\">Require e-mail verification when a visitor creates an account</a> setting, and is merely here for convenience). If selected, users will be assigned to the role below. They will not be assigned to the 'authenticated user' role until they confirm their e-mail address by following the link in their registration e-mail. It is HIGHLY recommended that you set up a 'pre-authorized' role with limited permissions for this purpose. <br />NOTE: If you enable this feature, you should edit the <a href=\"!settings\">Welcome (no approval required)</a> text.",
        array('@settings' => '/admin/config/people/accounts')) . $help_text,
  );

  // Grab the roles that can be used for pre-auth. Remove the anon role, as it's not a valid choice.
  $roles = user_role_names();
  $form ['registration']['pre_auth_role'] = array(
    '#type' => 'select',
    '#title' => $this->t('Non-authenticated role'),
    '#options' => $roles,
    '#default_value' => $config->get('pre_auth_role'),
    '#description' => $this->t('If "Set password" is selected, users will be able to login before their e-mail address has been authenticated. Therefore, you must choose a role for new non-authenticated users -- you may wish to <a href="@url">add a new role</a> for this purpose. Users will be removed from this role and assigned to the "authenticated user" role once they follow the link in their welcome e-mail. <strong>WARNING: changing this setting after initial site setup can cause undesirable results, including unintended deletion of users -- change with extreme caution!</strong>',
        array('@url' => '/admin/people/permissions/roles')),
  );

  $purge_options = array(
    0 => $this->t('Never delete'),
    86400 => $this->t('1 Day'),
    172800 => $this->t('2 Days'),
    259200 => $this->t('3 Days'),
    345600 => $this->t('4 Days'),
    432000 => $this->t('5 Days'),
    518400 => $this->t('6 Days'),
    604800 => $this->t('1 Week'),
    1209600 => $this->t('2 Weeks'),
    2592000 => $this->t('1 Month'),
    7776000 => $this->t('3 Months'),
    15379200 => $this->t('6 Months'),
    30758400 => $this->t('1 Year'),
  );

  $form['registration']['purge_unvalidated_user_interval'] = array(
    '#type' => 'select',
    '#title' => $this->t('Delete unvalidated users after'),
    '#options' => $purge_options,
    '#default_value' => $config->get('purge_unvalidated_user_interval'),
    '#description' => $this->t("If enabled, users that are still in the 'Non-authenticated role' set above will be deleted automatically from the system, if the set time interval since their initial account creation has passed. This can be used to automatically purge spambot registrations. Note: this requires cron, and also requires that the 'Set password' option above is enabled. <strong>WARNING: changing this setting after initial site setup can cause undesirable results, including unintended deletion of users -- change with extreme caution! (please read the CAVEATS section of INSTALL.txt for important information on configuring this feature)</strong>")
  );

  $form['registration']['immediate_login_on_register'] = array(
    '#type' => 'checkbox',
    '#title' => $this->t('Immediate login'),
    '#default_value' => $config->get('immediate_login_on_register'),
    '#description' => $this->t("If set, the user will be logged in immediately after registering. Note this only applies if the 'Set password' option above is enabled."),
  );

  $form['registration']['redirect'] = array(
    '#type' => 'details',
    '#title' => $this->t('Redirections'),
    '#collapsed' => FALSE,
  );

  $form['registration']['redirect']['redirect_on_register'] = array(
    '#type' => 'textfield',
    '#title' => $this->t('Redirect path on registration'),
    '#default_value' => $config->get('redirect_on_register'),
    '#description' => $this->t('Normally, after a user registers a new account, they will be taken to the front page, or to their user page if you specify <cite>Immediate login</cite> above. Leave this setting blank if you wish to keep the default behavior. If you wish the user to go to a page of your choosing, then enter the path for it here. For instance, you may redirect them to a static page such as <cite>node/35</cite>, or to the <cite>&lt;front&gt;</cite> page. You may also use <em>%uid</em> as a variable, and the user\'s user ID will be substituted in the path.'),
  );

  $form['registration']['redirect']['redirect_on_confirm'] = array(
    '#type' => 'textfield',
    '#title' => $this->t('Redirect path on confirmation'),
    '#default_value' => $config->get('redirect_on_confirm'),
    '#description' => $this->t('Normally, after a user confirms their new account, they will be taken to their user page. Leave this setting blank if you wish to keep the default behavior. If you wish the user to go to a page of your choosing, then enter the path for it here. For instance, you may redirect them to a static page such as <cite>node/35</cite>, or to the <cite>&lt;front&gt;</cite> page. You may also use <em>%uid</em> as a variable, and the user\'s user ID will be substituted in the path. In the case where users are not creating their own passwords, it is suggested to use <cite>user/%uid/edit</cite> here, so the user may set their password immediately after validating their account.'),
  );
  $form['registration']['redirect']['override_destination_parameter'] = array(
    '#type' => 'checkbox',
    '#title' => $this->t('Override destination parameter'),
    '#default_value' => $config->get('override_destination_parameter'),
    '#description' => $this->t("Normally, when a Drupal redirect is performed, priority is given to the 'destination' parameter from the originating URL. With this setting enabled, LoginToboggan will attempt to override this behavior with any values set above."),
  );

  $form['other'] = array(
    '#type' => 'fieldset',
    '#title' => $this->t('Other'),
    '#tree' => FALSE,
  );

  $form['other']['site_403'] = array(
    '#type' => 'checkbox',
    '#title' => $this->t('Present login form on access denied (403)'),
    '#default_value' => $config->get('site_403'),
    '#description' => $this->t('Anonymous users will be presented with a login form along with an access denied message.')
  );
  $form['other']['login_successful_message'] = array(
    '#type' => 'checkbox',
    '#title' => $this->t('Display login successful message'),
    '#default_value' => $config->get('login_successful_message'),
    '#description' => $this->t('If enabled, users will receive a \'Log in successful\' message upon login.')
  );
  $min_pass_options = array($this->t('None'));
  for ($i = 2; $i < 30; $i++) {
    $min_pass_options[$i] = $i;
  }
  $form['other']['minimum_password_length'] = array(
    '#type' => 'select',
    '#title' => $this->t('Minimum password length'),
    '#options' => $min_pass_options,
    '#default_value' => $config->get('minimum_password_length'),
    '#description' => $this->t('LoginToboggan automatically performs basic password validation for illegal characters. If you would additionally like to have a minimum password length requirement, select the length here, or set to \'None\' for no password length validation.')
  );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
//    $config = $this->configFactory->get('logintoboggan.settings');
    parent::submitForm($form, $form_state);
    $config = $this->config('logintoboggan.settings');
    foreach ($form_state->getValues() as $key => $value) {
      if (!in_array($key, array('submit', 'form_build_id', 'form_token', 'form_id', 'op'))) {
        if ($key == 'user_email_verification') {
          $value = !$value;
        }
        $config->set($key,  $value);
      }
    }
    $config->save();
    if ($form['login']['unified_login']['#default_value'] != $form['login']['unified_login']['#value']) {
      drupal_set_message(t('Unified login setting was changed, menus have been rebuilt.'));
      // For some reason, a regular menu_rebuild() still leaves the old callbacks
      // cached -- doing it in a shutdown function seems to correct that issue.
      drupal_register_shutdown_function('menu_rebuild');
    }
  }
}
