<?php

namespace Drupal\logintoboggan\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\user\Entity\User;

class LogintobogganController implements ContainerInjectionInterface {

  public static function create(ContainerInterface $container) {
    return new static($container->get('module_handler'));
  }

  /**
   * This will return the output of the page.
   */
  public function logintobogganValidateEmail($user, $timestamp, $hashed_pass, $operation) {

    $account = User::load($user);
    $cur_account = \Drupal::currentUser();
    $config = \Drupal::config('logintoboggan.settings');

    // On validation the url appears to be firing twice
    // Adding this until that can be sorted
    if(!$cur_account->isAnonymous()) {
      $goto = 'user.page';
      if ($config->get('redirect_on_confirm')) {
        $goto = $config->get('redirect_on_confirm');
      }
      return new RedirectResponse(\Drupal::url($goto));
    }
    // Test here for a valid pre-auth -- if the pre-auth is set to the auth user, we
    // handle things a bit differently.

    $validating_id = logintoboggan_validating_id();

    $pre_auth = !\Drupal::config('user.settings')->get('verify_mail')
              && $validating_id != DRUPAL_AUTHENTICATED_RID;

    // No time out for first time login.
    // This conditional checks that:
    // - the user is still in the pre-auth role or didn't set
    //   their own password.
    // - the hashed password is correct.
    if (((\Drupal::config('user.settings')->get('verify_mail')
      && !$account->getLastLoginTime()) || ($pre_auth && $account->hasRole($validating_id)))
      && $hashed_pass == logintoboggan_eml_rehash($account, $timestamp, $account->getEmail())) {

      \Drupal::logger('logintoboggan')->notice('E-mail validation URL used for %name with timestamp @timestamp.', array(
        '%name'      => $account->getUsername(),
        '@timestamp' => $timestamp));

      _logintoboggan_process_validation($account);

      // Where do we redirect after confirming the account?
      $redirect = _logintoboggan_process_redirect(\Drupal::config('logintoboggan.settings')->get('redirect_on_confirm'), $account);

      switch ($operation) {
        // Proceed with normal user login, as long as it's open registration and their
        // account hasn't been blocked.
        case 'login':
          // Only show the validated message if there's a valid pre-auth role.
          if ($pre_auth) {
            drupal_set_message(t($config->get('message_preauth_validate')));
          }
          if ($account->isBlocked()) {
            drupal_set_message(t('Your account is currently blocked -- login cancelled.'), 'error');
            return new RedirectResponse(\Drupal::url('<front>'));
          }
          else {
            $edit = array();
            $redirect = logintoboggan_process_login($account, $redirect);
            return new RedirectResponse($redirect);
          }
          break;
        // Admin validation.
        case 'admin':
          if ($pre_auth) {
            // Mail the user, letting them know their account now has auth user perms.
            _user_mail_notify('status_activated', $account);
          }

          drupal_set_message(t('You have successfully validated %user.', array(
            '%user' => $account->getUsername(),
          )));

          return new RedirectResponse(\Drupal::url('user.edit', array('user' => $user)));
          break;

        // Catch all.
        default:
          drupal_set_message(t('You have successfully validated %user.', array(
            '%user' => $account->getUsername(),
          )));
          return new RedirectResponse(\Drupal::url('<front>'));
          break;
      }
    }
    else {
      $message = t("Sorry, you can only use your validation link once for security reasons.");
      // No one currently logged in, go straight to user login page.
      if ($cur_account->isAnonymous()) {
        $message .= t(" Please log in with your username and password instead now.");
        $goto = 'user.login';
      }
      else {
        $goto = 'user.page';
      }
      drupal_set_message($message, 'error');
      return new RedirectResponse(\Drupal::url($goto));
    }
  }

  /**
   * This will return the output of the page.
   */
  public function logintobogganResendValidation($user) {
    $account = User::load($user);
    /**************************************************************************/
    $account->password = t('If required, you may reset your password from: !url', array(
      '!url' => url('user/password', array('absolute' => TRUE)),
    ));
    /**************************************************************************/

    _user_mail_notify('register_no_approval_required', $account);

    // Notify admin or user that e-mail was sent and return to user edit form.
    if (\Drupal::currentUser()->hasPermission('administer users')) {
      drupal_set_message(t("A validation e-mail has been sent to the user's e-mail address."));
    }
    else {
      drupal_set_message(t('A validation e-mail has been sent to your e-mail address. You will need to follow the instructions in that message in order to gain full access to the site.'));
    }

    return new RedirectResponse(\Drupal::url('user.edit', array('user' => $user)));
  }

  /**
   * Allow user to resend email validation by entering their email
   */
  public function logintobogganEmailValidation() {
    return \Drupal::formBuilder()->getForm('Drupal\logintoboggan\Form\LogintobogganEmailValidationForm');
  }

  /**
   * This will return the output of the page.
   */
  public function logintobogganDenied() {
    $account = \Drupal::currentUser();

    if ($account->isAnonymous()) {
      // Output the user login form.
      $page = logintoboggan_get_authentication_form('login');
      $page['#title'] = t('Access Denied / User log in');
    }
    else {
      $page = array(
        '#title'  => t('Access Denied'),
        '#theme' => 'lt_access_denied',
      );
    }

    return $page;
  }
}
