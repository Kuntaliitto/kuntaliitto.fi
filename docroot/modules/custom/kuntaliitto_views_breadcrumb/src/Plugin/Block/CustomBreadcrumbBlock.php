<?php

namespace Drupal\kuntaliitto_views_breadcrumb\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides a Custom Breadcrumb block.
 *
 * @Block(
 *   id = "custom_breadcrumb_block",
 *   admin_label = @Translation("Custom Breadcrumb Block"),
 *   category = @Translation("Custom")
 * )
 */
class CustomBreadcrumbBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    global $base_root, $base_path, $base_url;
    $path = '';
    $custom_breadcrumb = '';

    // Load current taxonomy name and use it for breadcrumb output.
    // Need it for special characters.
    $taxonomy_name = '';
    $current_uri = \Drupal::service('path.current')->getPath();
    $exploaded = explode('/', $current_uri);
    foreach ($exploaded as $key => $value) {
      if (empty($value)) {
        unset($exploaded[$key]);
      }
    }
    if (isset($exploaded[3]) && is_numeric($exploaded[3])) {
      $term = Term::load($exploaded[3]);
      if ($term) {
        $taxonomy_name = $term->getName();
      }
    }

    // Home icon to first breadcrumb element.
    $home_icon = $base_root . $base_path . drupal_get_path('module', 'kuntaliitto_views_breadcrumb') . '/images/home.png';
    // Get URL to create breadcrumb.
    $current_uri = \Drupal::request()->getRequestUri();
    // Get URL arguments.
    $arguments = explode('/', $current_uri);
    // Remove empty values.
    foreach ($arguments as $key => $value) {
      if (empty($value)) {
        unset($arguments[$key]);
      }
    }
    $arguments = array_values($arguments);
    // Generate LI items.
    if (!empty($arguments)) {
      foreach ($arguments as $key => $value) {
        $path .= "/$value";
        $value = ucfirst($value);
        // Don't make last breadcrumb a link.
        if ($key != (count($arguments) - 1)) {
          $custom_breadcrumb .= "<li><a href=\"$path\">$value</a></li>";
        }
        else {
          if ($taxonomy_name) {
            $value = $taxonomy_name;
          }
          $custom_breadcrumb .= "<li>$value</li>";
        }
      }
    }

    $breadcrumb_body = "<nav class=\"breadcrumb\" role=\"navigation\" aria-labelledby=\"system-breadcrumb\">
    <h2 id=\"system-breadcrumb\" class=\"visually-hidden\">Murupolku</h2>
    <ol><li>
      <a class=\"breadcrumb__home\" href=\"$base_url\">
      <span class=\"visually-hidden\">Home</span>            
        <span class=\"svg-icon svg-icon--home\">
          <img alt=\"Home icon\" src=\"$home_icon\" />
        </span>
      </a>
      $custom_breadcrumb    
    </ol></nav>";
    return [
      '#type' => 'markup',
      '#markup' => $breadcrumb_body,
      '#cache' => ['max-age' => 0],
    ];
  }

}
