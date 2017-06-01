<?php
/**
 * @file
 * Contains \Drupal\logintoboggan\Form\LogintobogganEmailValidationForm.
 */

namespace Drupal\logintoboggan\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\logintoboggan\LogintobogganTrait;
/**
 * Send out email validation
 */
class LogintobogganEmailValidationForm extends FormBase {

  use LogintobogganTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'logintoboggan_email_validation';
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

    $form['explanation'] = array(
      '#markup' => '<p>' . t('Use this form to resend yourself an email validation link for this site.')
    );

    $form['email'] = array(
      '#type' => 'textfield',
      '#title' => t('Email Address'),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Send')
    );

    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check that user exists
    $mail = $form_state->getValue('email');
    $account = user_load_by_mail($mail);
    if (!$account) {
      $form_state->setErrorByName('email', t('That email address is not registered.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $mail = $form_state->getValue('email');
    $this->validateEmailSend($mail);

  }
}
