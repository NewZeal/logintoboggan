<?php

namespace Drupal\logintoboggan;

/**
 *
 */
trait LogintobogganTrait {

  function validateEmailSend($mail) {
    $account = user_load_by_mail($mail);
    $params['account'] = $account;
    // Send out email
    $op = 'resend_email_validation';

    $result = _user_mail_notify($op, $account);

    if ($result['result'] !== true) {
      drupal_set_message(t('Please check your email account and click on the validation link to validate your email address .'));

    }
    else {
      drupal_set_message(t('There was a problem sending your message and it was not sent.'), 'error');
    }
  }

}
