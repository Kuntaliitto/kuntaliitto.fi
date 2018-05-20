<?php

namespace Drupal\kuntaliitto_common\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\TermInterface;
use Drupal\views\Views;

/**
 * Class KuntaTermController.
 *
 * @package Drupal\kuntaliitto_common\Controller
 */
class KuntaTermController extends ControllerBase {

  /**
   * Renders response view for taxonomy links.
   *
   * @param \Drupal\taxonomy\TermInterface $taxonomy_term
   *   Taxonomy term used on route.
   *
   * @return array|null
   *   A renderable array containing the view output or NULL if the display ID
   *   of the view to be executed doesn't exist.
   */
  public function render(TermInterface $taxonomy_term) {
    $vocabulary = $taxonomy_term->getVocabularyId();
    $view = [];
    if ($vocabulary == 'municipalities') {
      $view = ['id' => 'municipality', 'display' => 'page_1'];
    }

    if ($vocabulary == 'content_types') {
      switch ($taxonomy_term->id()) {
        //TODO: Remove this hardcoded stuff: use taxonomy term name?
        case (571):
          $view = ['id' => 'newsroom', 'display' => 'newsroom_press_release_page'];
          break;

        case (573):
          $view = ['id' => 'newsroom', 'display' => 'newsroom_recent_page'];
          break;

        case (622):
          $view = ['id' => 'newsroom', 'display' => 'newsroom_blog_page'];
          break;

        case (646):
          $view = ['id' => 'newsroom', 'display' => 'page_1'];
          break;

        case (623):
          $view = ['id' => 'newsroom', 'display' => 'newsroom_kuntalehti_page'];
          break;

        case (572):
          $view = ['id' => 'newsroom', 'display' => 'newsroom_kuntatekniikka_page'];
          break;

        case (567):
          $view = ['id' => 'events', 'display' => 'events_upcoming_tab'];
          break;

        case (565):
          $view = ['id' => 'newsroom', 'display' => 'newsroom_circular_page'];
          break;

        case (569):
          $view = ['id' => 'newsroom', 'display' => 'newsroom_experts_statements_page'];
          break;

        default:
          $view = ['id' => 'taxonomy_term', 'display' => 'page_1'];
      }
    }

    if ($vocabulary == 'bloggers') {
      $view = ['id' => 'taxonomy_term', 'display' => 'page_1'];
    }

    if (isset($view['id']) && isset($view['display'])) {
      return Views::getView($view['id'])
        ->executeDisplay($view['display'], [$taxonomy_term->id()]);
    }

    return Views::getView('solr_search')
      ->executeDisplay('page_solr_search', [
        'f' => [$vocabulary . ':' . $taxonomy_term->id()],
        's' => '',
      ]);
  }

}
