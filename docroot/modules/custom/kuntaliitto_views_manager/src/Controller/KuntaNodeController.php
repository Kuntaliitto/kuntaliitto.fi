<?php

namespace Drupal\kuntaliitto_views_manager\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Controller\NodeViewController;
use Drupal\views\Views;

/**
 * Defines a controller to render a single node.
 */
class KuntaNodeController extends NodeViewController {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $node, $view_mode = 'full', $langcode = NULL) {

    $displays = \Drupal::config('kuntaliitto_views_manager.newsroomconfig')->get();
    if ($display = array_search($node->id(), $displays)) {
      return Views::getView('newsroom')->executeDisplay($display);
    }
    $build = parent::view($node, $view_mode, $langcode);
    return $build;
  }

}
