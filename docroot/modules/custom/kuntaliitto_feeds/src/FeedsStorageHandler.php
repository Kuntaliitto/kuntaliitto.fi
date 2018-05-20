<?php

namespace Drupal\kuntaliitto_feeds;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\kuntaliitto_feeds\Entity\FeedConfigEntity;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class FeedsStorageHandler.
 *
 * @package Drupal\kuntaliitto_feeds
 */
class FeedsStorageHandler implements FeedsStorageHandlerInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * EntityTypeManager used to get entity storage.
   *
   * @protected Drupal\Core\Entity\Query\QueryFactory $entityQuery
   */
  protected $entityQuery;

  /**
   * FeedConfigEntity configurations in array.
   *
   * @var array
   */
  protected $configurations;

  /**
   * FeedsStorageHandler constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   */
  public function __construct(EntityTypeManager $entity_type_manager, QueryFactory $entity_query) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityQuery = $entity_query;
  }

  /**
   * Starts process of saving feed items to nodes using predefined array
   *  of feed items.
   *
   * @param array $feed_items
   * @param \Drupal\kuntaliitto_feeds\Entity\FeedConfigEntity $feed_config_entity
   */
  public function processToNode(array $feed_items, FeedConfigEntity $feed_config_entity) {
    $this->configurations = $feed_config_entity->toArray();
    $feed_items = $this->removeExisting($feed_items);
    $this->setUpSourceTerm();
    $items_processed = [];
    foreach ($feed_items as $item) {
      $this->preprocess($item);
      $items_processed[] = $this->saveFeedToNode($item);
    }
    $message = 'Successfully processed ' . $this->configurations['label'] . '. Total ';
    $message .= count($items_processed) . ' items processed.';
    drupal_set_message($message, 'status');
  }

  /**
   * Remove feed items from array if they already exist in database.
   *
   * @param array $feed_items
   *
   * @return array
   */
  public function removeExisting(array $feed_items) {
    foreach ($feed_items as $feed_item_key => $feed_item) {
      $nodes = $this->entityQuery->get('node')
        ->condition('title', $feed_item['title'])
        ->condition('type', $this->configurations['import_node'])
        ->execute();
      if (count($nodes) > 0) {
        unset($feed_items[$feed_item_key]);
      }
    }
    return $feed_items;
  }

  /**
   *
   */
  public function setUpSourceTerm() {
    $source = $this->configurations['content_source'];
    /** @var  \Drupal\taxonomy\TermStorage $termStorage */
    if ($term_id = $this->termExists($source, 'content_types')) {
      $this->configurations['content_source_id'] = $term_id;
    }
    else {
      drupal_set_message('Source term does not exist: ' . $source, 'error');
    }
  }

  /**
   * Dispatches feed item to image, term or other prepossessing if needed.
   *
   * @param array $item
   */
  public function preprocess(array &$item) {
    if ($this->configurations['has_image']) {
      $this->setImageId($item);
    }
    $this->setTermIds($item);
  }

  /**
   * Sets image attributes (id and alt) for image field of feed item.
   *
   * @param $item
   */
  public function setImageId(&$item) {
    if (!empty($item['image'])) {
      if ($this->configurations['has_image']) {
        $file_id = $this->entityQuery->get('file')
          ->condition('filename', basename($item['image']))
          ->execute();
        if ($file_id) {
          $image_id = reset($file_id);
        }
        else {
          $data = file_get_contents($item['image']);
          $file = file_save_data($data, 'public://media/image/' . basename($item['image']), FILE_EXISTS_REPLACE);
          $image_id = $file->id();
        }
        $item['image'] = [
          'target_id' => $image_id,
          'alt' => $this->getAltText($image_id),
        ];
      }
    }
    else {
      $item['image'] = [];
    }
  }

  /**
   * Convert image name to human readable text.
   *
   * @param $image_id
   *
   * @return string
   */
  public function getAltText($image_id) {
    /** @var \Drupal\file\Entity\File $image_entity */
    $image_entity = $this->entityTypeManager->getStorage('file')->load($image_id);
    $image_base_name = explode('.', $image_entity->getFilename())[0];
    return str_replace(['-', '_'], ' ', $image_base_name);
  }

  /**
   * Sets feed term id(s)
   *
   * @param $item
   */
  public function setTermIds(&$item) {
    /** @var  \Drupal\taxonomy\TermStorage $termStorage */
    foreach ($item['terms'] as $term_key => $term_value) {
      if ($term_id = $this->termExists($term_value, $this->configurations['vocabulary'])) {
        $item['terms'][] = $term_id;
      }
      unset($item['terms'][$term_key]);
    }
  }

  /**
   * Checks if term exists and returns first id if so.
   *
   * @param $term_name
   *
   * @return bool|mixed
   */
  public function termExists($term_name, $vocabulary) {
    $term_id = $this->entityQuery->get('taxonomy_term')
      ->condition('name', $term_name)
      ->condition('vid', $vocabulary)
      ->execute();
    if (count($term_id) > 0) {
      return reset($term_id);
    }
    return FALSE;
  }

  /**
   * Create node entity with feed item data and save it to database.
   *
   * @param $item
   *
   * @return int
   */
  public function saveFeedToNode($item) {
    $publish_till = NULL;
    if (!empty($this->configurations['publish_for'])) {
      $publish_till = REQUEST_TIME + ($this->configurations['publish_for'] * 60 * 60 * 24);
    }
    $feed_node = [
      'type' => $this->configurations['import_node'],
      'title' => $item['title'],
      'langcode' => $this->configurations['feed_language'],
      $this->configurations['image_field'] => $item['image'],
      $this->configurations['content_field'] => [
        'value' => $item['content'],
        'format' => $this->configurations['format'],
      ],
      'field_link' => [
        'uri' => $item['link'],
        'title' => $item['title'],
      ],
      'unpublish_on' => $publish_till,
      'created' => $item['published'],
      'updated' => $item['published'],
      $this->configurations['term_field'] => $item['terms'],
      'field_content_source' => $this->configurations['content_source_id'],
    ];
    if ($this->configurations['has_image']) {
      $feed_node[$this->configurations['image_field']] = $item['image'];
    }
    /** @var \Drupal\node\Entity\Node $feed_node */
    $feed_node = $this->entityTypeManager->getStorage('node')->create($feed_node);
    if ($this->configurations['published_status']) {
      $feed_node->setPublished(TRUE);
    }
    echo $item['published'] . PHP_EOL;
    return $feed_node->save();
  }

}
