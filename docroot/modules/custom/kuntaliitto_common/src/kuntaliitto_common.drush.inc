<?php

/**
 * @file
 * Provides drush commands for kuntaliitto_common module.
 */

/**
 * Implements hook_drush_command().
 */
function kuntaliitto_common_drush_command() {
  $items = [];
  $items['removeoldcontacts'] = [
    'description' => 'Removes users with extension OLD and fixes new users in contact information',
    'aliases' => ['roc'],
    'callback' => 'drush_kuntaliitto_common_remove_old_contacts',
  ];
  $items['remove-product-groups'] = [
    'description' => 'Delete PG taxonomy terms.',
    'aliases' => ['rpg'],
    'callback' => 'drush_kuntaliitto_common_remove_product_groups',
  ];

  return $items;
}

/**
 * Call back function for removeoldcontacts drush command.
 */
function drush_kuntaliitto_common_remove_old_contacts() {
  $query = \Drupal::entityQuery('user');
  $group = $query->orConditionGroup()
    ->condition('name', '%OLD', 'LIKE')
    ->condition('name', 'OLD%', 'LIKE')
    ->condition('name', 'old%', 'LIKE');
  $uids = $query->condition($group)->execute();
  $users = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple($uids);
  /** @var \Drupal\user\Entity\User $old_user */
  foreach ($users as $old_user) {
    $status = 'Deleted user ' . $old_user->getAccountName() . PHP_EOL;
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'contact_information')
      ->condition('field_user_reference', $old_user->id())
      ->execute();
    if ($nids) {
      foreach ($nids as $nid) {
        \Drupal::entityTypeManager()->getStorage('node')->load($nid)->delete();
        $status .= '  Deleted node: ' . $nid;
      }
    }
    user_delete($old_user->id());
    drush_print($status);
  }

  $nids = \Drupal::entityQuery('node')
    ->condition('type', 'contact_information')
    ->condition('uid', 1)
    ->execute();
  foreach ($nids as $nid) {
    $node = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->load($nid);
    $uid = $node->get('field_user_reference')->target_id;
    $users = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->getQuery()
      ->condition('uid', $uid)
      ->execute();
    if (!$users) {
      drush_print('Deleted node: ' . $node->id() . ' Info:' . $node->title->value);
      $node->delete();
    }
  }

}

/**
 * Call back function for remove poduct groups drush command.
 */
function drush_kuntaliitto_common_remove_product_groups() {
  $terms = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->getQuery()
    ->condition('vid', 'product_groups')
    ->execute();

  $terms_group = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadMultiple(array_values($terms));

  \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->delete($terms_group);
}
