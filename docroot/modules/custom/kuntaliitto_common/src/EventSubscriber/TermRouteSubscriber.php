<?php

namespace Drupal\kuntaliitto_common\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RoutingEvents;

/**
 * Class TermRouteSubscriber.
 *
 * @package Drupal\kuntaliitto_common\Routing
 * Listens to the dynamic route events.
 */
class TermRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.taxonomy_term.canonical')) {
      /** @var \Symfony\Component\Routing\Route $route */
      $route->setDefaults(
        ['_controller' => '\Drupal\kuntaliitto_common\Controller\KuntaTermController::render']
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
