<?php

namespace Drupal\kuntaliitto_views_manager\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @file
 * Contains \Drupal\kuntaliitto_views_manager\Routing\NewsroomRoutes.
 */

/**
 * Defines dynamic routes.
 */
class NewsroomRoutes {

  /**
   * {@inheritdoc}
   */
  public function routes() {

    $route_collection = new RouteCollection();
    $data = [
      [
        'route' => '/blog/{year}',
        'name' => 'blog',
        'display' => 'newsroom_blog_page',
      ],
      [
        'route' => '/recent/{year}',
        'name' => 'recent',
        'display' => 'newsroom_recent_page',
      ],
      [
        'route' => '/circular/{year}',
        'name' => 'circular',
        'display' => 'newsroom_circular_page',
      ],
      [
        'route' => '/pressreleases/{year}',
        'name' => 'pressreleases',
        'display' => 'newsroom_press_release_page',
      ],
      [
        'route' => '/experts-statements/{year}',
        'name' => 'experts-statements',
        'display' => 'newsroom_experts_statements_page',
      ],
      [
        'route' => '/kuntatekniikka/{year}',
        'name' => 'kuntatekniikka',
        'display' => 'newsroom_kuntatekniikka_page',
      ],
      [
        'route' => '/kuntalehti/{year}',
        'name' => 'kuntalehti',
        'display' => 'newsroom_kuntalehti_page',
      ],
      [
        'route' => '/kommuntorget/{year}',
        'name' => 'kommuntorget',
        'display' => 'newsroom_kommuntorget_page',
      ],
    ];

    foreach ($data as $path) {
      $route = new Route(
        // Path to attach this route to:
        $path['route'],
        // Route defaults:
        [
          '_controller' => '\Drupal\kuntaliitto_views_manager\Controller\CustomViewsController::newsroom',
          'year' => 'all',
          'display' => $path['display'],
        ],
        // Route requirements:
        [
          '_permission'  => 'access content',
        ]
      );
      // Add the route under the name 'example.content'.
      $route_collection->add($path['name'], $route);
    }
    return $route_collection;
  }

}
