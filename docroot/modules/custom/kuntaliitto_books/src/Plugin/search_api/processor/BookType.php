<?php

namespace Drupal\kuntaliitto_books\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Adds the item's URL to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "book_id",
 *   label = @Translation("Book Id"),
 *   description = @Translation("Adds the Book Id to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class BookType extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Book ID'),
        'description' => $this->t('The Book ID'),
        'type' => 'integer',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['book_id'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $entity = $item->getOriginalObject()->getValue();
    $bundle_entity_type = $entity->getEntityType()->get('bundle_entity_type');
    if ($bundle_entity_type && $storage = \Drupal::entityTypeManager()->getStorage($bundle_entity_type)) {
      $bundle_display = $storage
        ->load($entity->bundle())
        ->label();
    }

    if ($entity->getEntityType()->id() == 'node') {
      if (isset($entity->book['bid'])) {
        $bid = $entity->book['bid'];
      }
    }

    if (isset($bid)) {
      foreach ($this->getFieldsHelper()->filterForPropertyPath($item->getFields(), NULL, 'book_id') as $field) {
        if (!$field->getDatasourceId()) {
          $field->addValue($bid);
        }
      }
    }
  }

}
