<?php

namespace Drupal\kuntaliitto_feeds;

/**
 * Interface XmlParserInterface.
 *
 * @package Drupal\kuntaliitto_feeds
 */
interface XmlParserInterface {

  /**
   * Processes ids and returns process status.
   *
   * @param array $feed_config_ids
   *
   * @return bool
   */
  public function process(array $feed_config_ids);

}
