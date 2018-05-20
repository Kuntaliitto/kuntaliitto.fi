<?php

namespace Drupal\kuntaliitto_feeds;

use Drupal\kuntaliitto_feeds\Entity\FeedConfigEntity;

/**
 * Interface FeedsStorageHandlerInterface.
 *
 * @package Drupal\kuntaliitto_feeds
 */
interface FeedsStorageHandlerInterface {

  /**
   * Starts process of saving feed items to nodes using predefined array
   *  of feed items.
   *
   * @param array $feed_items
   * @param \Drupal\kuntaliitto_feeds\Entity\FeedConfigEntity $feed_config_entity
   */
  public function processToNode(array $feed_items, FeedConfigEntity $feed_config_entity);

}
