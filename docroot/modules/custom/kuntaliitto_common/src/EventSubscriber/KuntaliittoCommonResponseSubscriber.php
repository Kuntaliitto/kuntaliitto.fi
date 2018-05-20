<?php

namespace Drupal\kuntaliitto_common\EventSubscriber;

use Drupal\google_tag\EventSubscriber\GoogleTagResponseSubscriber;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Render\HtmlResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Class KuntaliittoCommonResponseSubscriber.
 *
 * @package Drupal\kuntaliitto_common\EventSubscriber
 */
class KuntaliittoCommonResponseSubscriber extends GoogleTagResponseSubscriber {

  /**
   * Adds taggs to response object.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   Event object to react upon.
   */
  public function addTag(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    $request = $event->getRequest();
    $response = $event->getResponse();

    if ($this->tagApplies($request, $response)) {
      // Custom get container id because of multible domains.
      $container_id = $this->getContainerIdByHost();
      $container_id = trim(json_encode($container_id), '"');
      $compact = $this->config->get('compact_tag');

      // Insert snippet after the opening body tag.
      $response_text = preg_replace('@<body[^>]*>@', '$0' . $this->getTag($container_id, $compact), $response->getContent(), 1);
      $response->setContent($response_text);
    }
  }

  /**
   * Determines whether or not the tag should be included on a request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request, used for path matching.
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response, used for matching status codes.
   *
   * @return bool
   *   Whether or not the tag should be included on the page.
   */
  private function tagApplies(Request $request, Response $response) {
    $id = $this->getContainerIdByHost();

    if (empty($id)) {
      // No container ID.
      return FALSE;
    }

    if (!$this->statusCheck($response) && !$this->pathCheck($request)) {
      // Omit snippet based on the response status and path conditions.
      return FALSE;
    }

    if (!$this->roleCheck()) {
      // Omit snippet based on the response status and path conditions.
      return FALSE;
    }

    if (!($response instanceof HtmlResponse)) {
      // Omit snippet because the response is not HTML.
      return FALSE;
    }

    return TRUE;
  }

  /**
   * HTTP status code check.
   *
   * This checks to see if status check is even used
   * before checking the status.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response object.
   *
   * @return bool
   *   True if the check is enabled and the status code matches the list of
   *   enabled statuses.
   */
  private function statusCheck(Response $response) {
    static $satisfied;

    if (!isset($satisfied)) {
      $toggle = $this->config->get('status_toggle');
      $statuses = $this->config->get('status_list');

      if (!$toggle) {
        return FALSE;
      }
      else {
        // Get the HTTP response status.
        $status = $response->getStatusCode();
        $satisfied = strpos($statuses, (string) $status) !== FALSE;
      }
    }
    return $satisfied;
  }

  /**
   * Determines if tag should be included on a page based on the path settings.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return bool
   *   True if the tag should be included for the current request based on path
   *   settings.
   */
  private function pathCheck(Request $request) {
    static $satisfied;

    if (!isset($satisfied)) {
      $toggle = $this->config->get('path_toggle');
      $pages = Unicode::strtolower($this->config->get('path_list'));

      if (empty($pages)) {
        return ($toggle == GOOGLE_TAG_DEFAULT_INCLUDE) ? TRUE : FALSE;
      }
      else {
        // Compare the lowercase path alias (if any) and internal path.
        $path = rtrim($this->currentPath->getPath($request), '/');
        $path_alias = Unicode::strtolower($this->aliasManager->getAliasByPath($path));
        $satisfied = $this->pathMatcher->matchPath($path_alias, $pages) || (($path != $path_alias) && $this->pathMatcher->matchPath($path, $pages));
        $satisfied = ($toggle == GOOGLE_TAG_DEFAULT_INCLUDE) ? !$satisfied : $satisfied;
      }
    }

    return $satisfied;
  }

  /**
   * Determines if tag should be included on a page based on user roles.
   *
   * @return bool
   *   True is the check is enabled and the user roles match the settings.
   */
  private function roleCheck() {
    static $satisfied;

    if (!isset($satisfied)) {
      $toggle = $this->config->get('role_toggle');
      $roles = $this->config->get('role_list');

      if (empty($roles)) {
        return ($toggle == GOOGLE_TAG_DEFAULT_INCLUDE) ? TRUE : FALSE;
      }
      else {
        $satisfied = FALSE;
        // Check user roles against listed roles.
        $satisfied = (bool) array_intersect($roles, $this->currentUser->getRoles());
        $satisfied = ($toggle == GOOGLE_TAG_DEFAULT_INCLUDE) ? !$satisfied : $satisfied;
      }
    }
    return $satisfied;
  }

  /**
   * Get method for Container ID.
   *
   * @return string
   *   String representation of Container ID.
   */
  private function getContainerIdByHost() {
    $host = \Drupal::request()->getHost();
    // TODO: should we add container id as config?
    $container_id = 'GTM-xxxx';
    if (strstr($host, 'avoin.kuntaliitto')) {
      $container_id = 'GTM-xxxx';
    }
    elseif (strstr($host, 'oppna.kommunforbundet')) {
      $container_id = 'GTM-xxxx';
    }
    return $container_id;
  }

}
