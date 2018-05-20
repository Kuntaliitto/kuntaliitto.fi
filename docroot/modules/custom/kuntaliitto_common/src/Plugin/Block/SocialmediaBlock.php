<?php

namespace Drupal\kuntaliitto_common\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Social Media' Block.
 *
 * @Block(
 *   id = "social_madia_block",
 *   admin_label = @Translation("Social Media block"),
 * )
 */
class SocialmediaBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => $this->markup(),
    ];
  }

  /**
   * Main method of block that returns social media links.
   *
   * @return string
   *   String representation of markup.
   */
  private function markup() {
    $markup = '<a class="twitter-icon" href="https://twitter.com/kuntaliitto"></a>';
    $markup .= '<a class="facebook-icon" href="https://www.facebook.com/kuntaliitto"></a>';
    $markup .= '<a class="linkedin-icon" href="https://www.linkedin.com/company/association-of-finnish-local-and-regional-authorities-kuntaliitto-"></a>';
    $markup .= '<a class="youtube-icon" href="https://www.youtube.com/kuntaliitto"></a>';

    return $markup;
  }

}
