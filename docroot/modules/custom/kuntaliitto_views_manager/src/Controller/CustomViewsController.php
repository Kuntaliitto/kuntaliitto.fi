<?php

namespace Drupal\kuntaliitto_views_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\views\Views;

/**
 * @file
 * Contains Drupal\kuntaliitto_views_manager\Controller for populating views.
 */

/**
 * Class CustomViewsController.
 *
 * @package Drupal\kuntaliitto_views_manager\Controller
 */
class CustomViewsController extends ControllerBase {

  /**
   * Return displays with sent years for appropriate routes.
   *
   * @param string $year
   *   Context year.
   * @param string $display
   *   Newsroom view display name.
   */
  public function newsroom($year, $display) {
    // Validate year.
    if (!preg_match('#^(19|20)\d{2}$#', $year)) {
      $year = 'all';
    }
    return Views::getView('newsroom')
      ->executeDisplay($display, [$year]);
  }

}
