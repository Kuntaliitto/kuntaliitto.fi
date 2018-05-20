<?php

namespace Drupal\kuntaliitto_common\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;

/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node_outbound_provider")
 */
class NodeOutboundProvider extends FieldPluginBase {

  /**
   * Called to add the field to a query.
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * Adds media bundle to bundle.
   *
   * @param \Drupal\views\ResultRow $values
   *   Result row object.
   *
   * @return mixed
   *   String representation of bundle.
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $values->_entity;
    $node = $node->getTranslation(\Drupal::languageManager()->getCurrentLanguage()->getId());

    if ($node->bundle() == 'external_article') {
      if (UrlHelper::isValid($node->field_link->uri)) {
        $attributes = [
          'class' => 'outbound-link',
          'hreflang' => $node->language()->getId(),
          'target' => '_blank',
          'rel' => 'noopener',
        ];
        $link = Link::fromTextAndUrl($node->getTitle(), Url::fromUri($node->field_link->uri, ['attributes' => $attributes]));
        return $link->toString();
      }
    }
    if ($node->bundle() == 'publication') {
      if (UrlHelper::isValid($node->field_url->uri)) {
        $attributes = [
          'class' => 'outbound-link',
          'hreflang' => $node->language()->getId(),
          'target' => '_blank',
          'rel' => 'noopener',
        ];
        $link = Link::fromTextAndUrl($node->getTitle(), Url::fromUri($node->field_url->uri, ['attributes' => $attributes]));
        return $link->toString();
      }
    }

    $url = Url::fromRoute('entity.node.canonical', ['node' => $node->id()], ['attributes' => ['hreflang' => $node->language()->getId()]]);
    $link = Link::fromTextAndUrl($node->getTitle(), $url);
    return $link->toString();

  }

}
