<?php

namespace Drupal\kuntaliitto_common\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewExecutable;

/**
 * Filters by given list of news keywords options.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("d8views_news_keywords")
 */
class NewsKeywords extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = t('Allowed media titles');
    $this->definition['options callback'] = [$this, 'generateOptions'];
  }

  /**
   * Override the query.
   *
   * No filtering takes place if the user doesn't select any options.
   */
  public function query() {
    if (!empty($this->value)) {
      parent::query();
    }
  }

  /**
   * Skip validation if no options have been chosen.
   */
  public function validate() {
    if (!empty($this->value)) {
      parent::validate();
    }
  }

  /**
   * Helper function that generates the options.
   *
   * @return array
   *   List of options.
   */
  public function generateOptions() {
    $keywords = [];
    $exposed_input = views_get_current_view()->getExposedInput();
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $group = \Drupal::service('entity.query')->get('node')->orConditionGroup()
      ->condition('type', 'news')
      ->condition('type', 'circular')
      ->condition('type', 'media_release');
    $query = \Drupal::service('entity.query')->get('node')
      ->condition($group)
      ->condition('langcode', $language)
      ->condition('status', 1);
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    if (isset($exposed_input['news_municipality'])) {
      if ($exposed_input['news_municipality'] != 'All') {
        $query->condition('field_municipalities.entity.tid', $exposed_input['news_municipality']);
      }
    }
    if (isset($exposed_input['news_topics'])) {
      if ($exposed_input['news_topics'] != 'All') {
        $query->condition('field_topics.entity.tid', $exposed_input['news_topics']);
      }
    }
    $nids = $query->execute();
    if ($nids) {
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $nodes = $node_storage->loadMultiple($nids);
      /** @var \Drupal\node\Entity\Node $node */
      foreach ($nodes as $node) {
        if ($node->get('field_keywords')->referencedEntities()) {
          foreach ($node->get('field_keywords')->referencedEntities() as $item) {
            if (!empty($item)) {
              $taxonomy_term_trans = \Drupal::service('entity.repository')->getTranslationFromContext($item, $language);
              $name_translated = $taxonomy_term_trans->name->value;
              $option = new \stdClass();
              $option->option = [$item->id() => $name_translated];
              $keywords[$name_translated] = $option;
            }
          }
        }
      }
    }
    ksort($keywords);
    return $keywords;
  }

}
