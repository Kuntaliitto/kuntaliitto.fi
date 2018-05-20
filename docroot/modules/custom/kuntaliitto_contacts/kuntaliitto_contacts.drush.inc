<?php

/**
 * @file
 * Drush commands for contacts.
 */

use Drupal\kuntaliitto_contacts\Exception\ContactImportException;

/**
 * Implements hook_drush_command().
 */
function kuntaliitto_contacts_drush_command() {
  $commands['import-contacts'] = [
    'description' => 'Imports and updates contact information.',
    'aliases' => ['imco'],
  ];

  return $commands;
}

/**
 * Drush contact import callback.
 */
function drush_kuntaliitto_contacts_import_contacts() {
  // Get data from external JSON.
  // TODO: Make this URL as configuration
  // Where to find LDAD data?
  $json = file_get_contents('https://www.domain.fi/app/ldap/person-list.php');
  if (!$json) {
    \Drupal::logger('Could not access json file')
      ->error(json_last_error());
    return FALSE;
  }
  $user_data = json_decode(utf8_encode($json), TRUE, 515, 4);
  if (json_last_error()) {
    \Drupal::logger('kuntaliito_contacts')
      ->error(json_last_error());
  }

  // Get data saved from previous import.
  $managed_users = \Drupal::state()->get('kuntaliitto_contacts.managed_user') ?: [];
  foreach ($user_data as $user_name => $user) {
    $managed_users[$user_name] = 'True';
    $contact_manager = \Drupal::service('kuntaliitto_contacts.process');
    try {
      $status = $contact_manager->process($user);
      drush_print($status . ': ' . $user_name);
    }
    catch (ContactImportException $e) {
      \Drupal::logger('kuntaliito_contacts')
        ->error($e->getMessage() . ':' . $user_name);
    }
  }

  // Find users that are managed but not in current data set.
  $users_accounted = array_keys($managed_users);
  $users_in_current_data = array_keys($user_data);
  $users_to_deactivate = array_diff($users_accounted, $users_in_current_data);
  foreach ($users_to_deactivate as $user_to_deactivate) {
    // If user have not been deactivated then deactivate it.
    if ($managed_users[$user_to_deactivate] != 'False') {
      $contact_manager = \Drupal::service('kuntaliitto_contacts.process');
      $managed_users[$user_to_deactivate] = 'False';
      try {
        $status = $contact_manager->deactivate($user_to_deactivate);
        drush_print($status . ': ' . $user_to_deactivate);
      }
      catch (ContactImportException $e) {
        \Drupal::logger('kuntaliito_contacts')
          ->error($e->getMessage() . ':' . $user_to_deactivate);
      }
    }
  }

  \Drupal::state()->set('kuntaliitto_contacts.managed_user', $managed_users);

}
