<?php

/**
 * @file
 * Provides drush commands for municipalities.
 */

/**
 * Implements hook_drush_command().
 */
function kuntaliitto_municipalities_drush_command() {
  $commands['import-municipalities'] = [
    'description' => 'Import municipalities',
    'aliases' => ['immu'],
  ];

  return $commands;
}

/**
 * Drush callback.
 *
 * Drush_[MODULE_NAME]_[COMMAND_NAME]().
 */
function drush_kuntaliitto_municipalities_import_municipalities() {
  $json = file_get_contents('http://app.kunnat.net/ldap/municipal-info-all.php');
  if (!$json) {
    drush_print('Could not access json file');
    return FALSE;
  }
  $municipalities_data = json_decode($json, TRUE);
  foreach ($municipalities_data as $municipality) {
    $municipalities_manager = \Drupal::service('kuntaliitto_municipalities.process');
    $status = $municipalities_manager->process($municipality);
    echo $status . ': ' . $municipality['nimi'] . PHP_EOL;
  }
}
