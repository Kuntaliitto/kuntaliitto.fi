<?php

namespace Drupal\kuntaliitto_views_manager\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RoutingEvents;

/**
 * Class NewsroomRouteSubscriber.
 *
 * @package Drupal\kuntaliitto_views_manager\Routing
 * Listens to the dynamic route events.
 */
class NewsroomRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.node.canonical')) {
      /** @var \Symfony\Component\Routing\Route $route */
      $route->setDefaults([
          '_controller' => '\Drupal\kuntaliitto_views_manager\Controller\KuntaNodeController::view',
          '_title_callback' => '\Drupal\node\Controller\NodeViewController::title',
        ]
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run after Views subscriber has ran.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -200];
    return $events;
  }

}
