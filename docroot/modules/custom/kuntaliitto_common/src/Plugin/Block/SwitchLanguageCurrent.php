<?php

namespace Drupal\kuntaliitto_common\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Printlink' block.
 *
 * @Block(
 *   id = "print_link_block",
 *   admin_label = @Translation("Printlinks Current"),
 *   category = @Translation("Custom")
 * )
 */
class SwitchLanguageCurrent extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Constructs an LanguageBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager, PathMatcherInterface $path_matcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $language_manager;
    $this->pathMatcher = $path_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager'),
      $container->get('path.matcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $access = $this->languageManager->isMultilingual() ? AccessResult::allowed() : AccessResult::forbidden();
    return $access->addCacheTags(['config:configurable_language_list']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\Core\Language\LanguageManagerInterface $language_manager */
    $language_manager = \Drupal::service('language_manager');
    $current = new Url('<current>');
    $links = $language_manager->getLanguageSwitchLinks('language_interface', $current);
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $switch_titles = [
      'en' => t('In English'),
      'fi' => t('In Finnish'),
      'sv' => t('In Swedish'),
    ];

    foreach ($links->links as $link_key => $link) {
      /** @var \Drupal\node\Entity\Node $node */
      if ($node = \Drupal::routeMatch()->getParameter('node')) {
        if (is_string($node)) {
          $node = Node::load($node);
        }
        if (!$node->hasTranslation($link_key)) {
          unset($links->links[$link_key]);
        }
      }
      /** @var \Drupal\taxonomy\TermInterface $term */
      elseif ($term = \Drupal::routeMatch()->getParameter('taxonomy_term')) {
        if (!$term->hasTranslation($link_key)) {
          unset($links->links[$link_key]);
        }
      }
    }
    $print_links = [];
    foreach ($links->links as $link_key => $link) {
      if ($language == $link_key) {
        unset($links->links[$link_key]);
      }
      else {
        $links->links[$link_key]['url'] = new Url('<current>');
        /** @var \Drupal\Core\Url $lang_url */
        $lang_url = $links->links[$link_key]['url'];
        $lang_url->setOption('language', $links->links[$link_key]['language']);
        $print_links[$link_key] = '<a href="' . $lang_url->toString() . '" class="language-link print-links" hreflang="' . $link_key . '">' . $switch_titles[$link_key] . '</a>';
      }
    }
    $html = "<div id='print-link-block'>";
    foreach ($print_links as $link) {
      $html .= $link;
    }
    $html .= '<a href="#" id="print-current-page" class="print-links" >' . t('Print') . '</a>';
    $html .= '</div>';

    $build = [
      '#markup' => $html,
      '#attached' => [
        'library' => ['kuntaliitto_common/print_link'],
      ],
    ];

    return $build;

  }

  /**
   * {@inheritdoc}
   *
   * @todo Make cacheable in https://www.drupal.org/node/2232375.
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
