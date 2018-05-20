<?php

namespace Drupal\kuntaliitto_common\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewExecutable;

/**
 * Filters by given list of event location options.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("d8views_event_location")
 */
class EventLocation extends InOperator {

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
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    // Array keys are used to compare with the table field values.
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'event')
      ->condition('status', 1)
      ->condition('langcode', $language)
      ->execute();
    $media_entities = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
    $list_items = [];
    /** @var \Drupal\node\Entity\Node $entity */
    foreach ($media_entities as $entity) {
      $location = $entity->field_event_city->getValue()[0]['value'];
      $list_items[$location] = $location;
    }
    ksort($list_items);
    return $list_items;
  }

}
