<?php

namespace Drupal\kuntaliitto_liftup\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewExecutable;

/**
 * Filters by given list of media title options.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("d8views_media_titles")
 */
class MediaTitles extends InOperator {

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
   * No filtering takes place if the user doesn't
   * select any options.
   */
  public function query() {
    if (!empty($this->value)) {
      parent::query();
    }
  }

  /**
   * Skip validation.
   *
   * Skip if no options have been chosen so we can use it as a
   * non-filter.
   */
  public function validate() {
    if (!empty($this->value)) {
      parent::validate();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function generateOptions() {
    // Array keys are used to compare with the table field values.
    $mids = \Drupal::entityQuery('media')
      ->condition('bundle', 'image_bank')
      ->condition('status', 1)
      ->execute();;
    $media_entities = \Drupal::entityTypeManager()->getStorage('media')->loadMultiple($mids);
    $list_items = [];
    foreach ($media_entities as $entity) {
      $list_items[$entity->label()] = $entity->label();
    }

    return $list_items;
  }

}
