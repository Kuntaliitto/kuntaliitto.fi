<?php

/**
 * @file
 */

/**
 * Implements hook_drush_command().
 */
function kuntaliitto_feeds_drush_command() {
  $items = [];
  $items['import-feeds'] = [
    'description' => 'Imports event nodes from using event configurations',
    'aliases' => ['imfe'],
    'callback' => 'drush_kuntaliitto_feeds_import_feeds',
  ];
  return $items;
}

/**
 * Callback function for drush command import-feeds.
 */
function drush_kuntaliitto_feeds_import_feeds() {
  $entity_ids = \Drupal::service('entity.query')
    ->get('feed_config_entity')
    ->condition('feed_status', 'true')
    ->execute();
  $entities = \Drupal::service('entity_type.manager')
    ->getStorage('feed_config_entity')
    ->loadMultiple($entity_ids);
  \Drupal::service('kuntaliitto_feeds.xml_parser')->process(array_keys($entities));
}
