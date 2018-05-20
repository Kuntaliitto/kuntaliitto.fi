<?php

namespace Drupal\kuntaliitto_common\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("media_bundle_provider")
 */
class MediaBundleProvider extends FieldPluginBase {

  /**
   * Called to add the field to a query.
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * Adds media bundle to bundle.
   *
   * @param \Drupal\views\ResultRow $values
   *   Result row object.
   *
   * @return mixed
   *   String representation of bundle.
   */
  public function render(ResultRow $values) {
    $bundle_info = \Drupal::service("entity_type.bundle.info")->getAllBundleInfo();
    $media = $values->_entity;
    $bundle = $bundle_info[$media->getEntityTypeId()][$media->bundle()]['label'];
    return $bundle;
  }

}
