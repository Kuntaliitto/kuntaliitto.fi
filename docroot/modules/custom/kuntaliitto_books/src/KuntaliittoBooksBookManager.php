<?php

namespace Drupal\kuntaliitto_books;

use Drupal\book\BookManager;

/**
 * Description of KuntaliittoBooksBookManager.
 *
 * @author aleksandrsuhhinin
 */
class KuntaliittoBooksBookManager extends BookManager {

  /**
   * {@inheritdoc}
   */
  public function bookTreeCheckAccess(&$tree, $node_links = []) {
    if ($node_links) {
      // @todo Extract that into its own method.
      $nids = array_keys($node_links);

      // @todo This should be actually filtering on the desired node status
      //   field language and just fall back to the default language.
      $nids = \Drupal::entityQuery('node')
        ->condition('nid', $nids, 'IN')
        ->execute();
      foreach ($nids as $nid) {
        // Checks view permission for current user.
        $access_control_handler = \Drupal::entityTypeManager()->getAccessControlHandler('node');
        $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
        $access = $access_control_handler->access($node, 'view', \Drupal::currentUser());
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $translated = FALSE;
        if ($node->isTranslatable()) {
          if ($node->hasTranslation($language)) {
            $translated = TRUE;
          }
        }
        if ($access && $translated) {
          foreach ($node_links[$nid] as $mlid => $link) {
            $node_links[$nid][$mlid]['access'] = TRUE;
            $node_links[$nid][$mlid]['status'] = $node->status->value;
          }
        }
      }
    }
    $this->doBookTreeCheckAccess($tree);
  }

}
