<?php

/**
 * @file
 * Contains \Drupal\logintoboggan\Access\LogintobogganValidateAccess.
 */

namespace Drupal\logintoboggan\Access;

use Drupal\Core\Routing\Access\AccessInterface as RoutingAccessInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Drupal\user\Entity\User;

/**
 * Determines access to routes based on login status of current user.
 */
class LogintobogganValidateAccess implements RoutingAccessInterface {

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_logintoboggan_validate_email_access');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route) {
    $current_path = \Drupal::service('path.current')->getPath();
    $args = explode('/', $current_path);
    $account = User::load($args[3]);
    if (is_object($account)) {
      return AccessResult::allowedIf($account->isAuthenticated() && $args[4] < REQUEST_TIME);
    }

    else {
      \Drupal::logger('logintoboggan')->notice('validate access failure '. print_r($args,1));
      return AccessResult::forbidden();
    }
  }
}
