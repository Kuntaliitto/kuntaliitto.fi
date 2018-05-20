<?php

namespace Drupal\kuntaliitto_contacts;

/**
 * Interface ContactProcessorInterface.
 *
 * @package Drupal\kuntaliitto_contacts
 */
interface ContactProcessorInterface {

  /**
   * @param array $user
   * @return string
   */
  public function process(array $user);

  /**
   * @param string $username
   * @return string
   */
  public function deactivate($username);

}
