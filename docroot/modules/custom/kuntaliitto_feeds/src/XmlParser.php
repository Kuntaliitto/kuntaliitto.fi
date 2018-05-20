<?php

namespace Drupal\kuntaliitto_feeds;

use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use SimpleXMLElement;

/**
 * Class XmlParser.
 *
 * @package Drupal\kuntaliitto_feeds
 */
class XmlParser implements XmlParserInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManager to manipulate Feed config entity.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\kuntaliitto_feeds\FeedsStorageHandler saves feed items to nodes.
   *
   * @var \Drupal\kuntaliitto_feeds\FeedsStorageHandler
   */
  protected $feedsStorageHandler;

  /**
   * FeedConfigEntity configurations in array.
   *
   * @var array
   */
  protected $configurations;

  /**
   * Array containing terms to remove.
   *
   * @var array
   */
  protected $removable_terms;

  /**
   * Associative array containing key term to replace with value of term with
   *  what it will be replaced.
   *
   * @var array
   */
  protected $replaceable_terms;

  /**
   * Associative array containing key term to add by default.
   *
   * @var array
   */
  protected $default_terms;

  /**
   * XmlParser constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   * @param \Drupal\kuntaliitto_feeds\FeedsStorageHandler $feeds_storage_handler
   */
  public function __construct(EntityTypeManager $entity_type_manager, FeedsStorageHandler $feeds_storage_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->feedsStorageHandler = $feeds_storage_handler;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('kuntaliitto_feeds.storage')
    );
  }

  /**
   * Processes ids and returns process status.
   *
   * @param array $feed_config_ids
   *
   * @return bool
   */
  public function process(array $feed_config_ids) {
    foreach ($feed_config_ids as $feed_config_id) {
      /** @var \Drupal\kuntaliitto_feeds\Entity\FeedConfigEntity $feed_config_entity  */
      $feed_config_entity = $this->entityTypeManager->getStorage('feed_config_entity')->load($feed_config_id);
      $this->configurations = $feed_config_entity->toArray();
      // Set term jobs because they are saved as user input strings and we need arrays.
      $this->setTermDefinitions();
      if ($feed_items = $this->getFeedItems()) {
        $this->feedsStorageHandler->processToNode($feed_items, $feed_config_entity);
      }
    }
    return TRUE;
  }

  /**
   * Sets default terms, removable terms and replaceable terms array.
   */
  public function setTermDefinitions() {
    $this->default_terms = $this->getDefaultTerms($this->configurations['default_term']);
    $this->removable_terms = $this->getRemovableTerms($this->configurations['remove_terms']);
    $this->replaceable_terms = $this->getTermReplacements($this->configurations['replace_terms']);
  }

  /**
   * Sets feed item values in array.
   *
   * @return mixed
   */
  public function getFeedItems() {
    $data = file_get_contents($this->configurations['url']);
    $data = str_replace('xmlns:media="http://search.yahoo.com/mrss/"xmlns:media="http://search.yahoo.com/mrss/"', 'xmlns:media="http://search.yahoo.com/mrss/"', $data);
    /** @var \SimpleXMLElement $xml */
    $xml = simplexml_load_string($data);
    if (!$xml) {
      drupal_set_message('Could not load xml from url: ' . $this->configurations['url'], 'warning');
      return FALSE;
    }
    $feed_items = [];
    if ($this->configurations['has_wrapper']) {
      $wrapper = $this->configurations['wrapper'];
      $xml = $xml->$wrapper;
    }
    $xml_items = $xml->xpath($this->configurations['item_xpath']);
    foreach ($xml_items as $item) {
      $feed_items[] = $this->getItemElements($item);
    }
    return $feed_items;
  }

  /**
   * Sets feed item values in array.
   *
   * @param \SimpleXMLElement $item
   *
   * @return array
   */
  public function getItemElements(SimpleXMLElement $item) {
    $title = (string) $item->xpath($this->configurations['title_xpath'])[0];
    $content = (string) $item->xpath($this->configurations['content_xpath'])[0];
    $link = (string) $item->xpath($this->configurations['link_xpath'])[0];
    $image = ($this->configurations['has_image']) ?
      (string) $this->getImageUrl($item) : NULL;
    $published = $this->getPublishedDate($item);
    // If feed config states that there is no term tag return empty array
    // because we still might add default terms otherwise search all tags in xml.
    $terms = [];
    if ($this->configurations['has_terms']) {
      $terms = $this->getTerms($item, $this->configurations['term_xpath']);
    }

    $feed_item = [
      'title' => $title,
      'content' => $content,
      'link' => $link,
      'terms' => $this->filterTerms($terms),
      'image' => $image,
      'published' => $published,
    ];
    return $feed_item;
  }

  /**
   *
   */
  public function getPublishedDate(SimpleXMLElement $item) {
    $date = (string) $item->xpath($this->configurations['published_xpath'])[0];
    $dtime = \DateTime::createFromFormat($this->configurations['published_format'], trim($date));
    if ($dtime) {
      return $dtime->getTimestamp();
    }
    $dtime = new \DateTime();
    return $dtime->getTimestamp();
  }

  /**
   * Get feed item image url based on defined feed entity configurations.
   *
   * @param \SimpleXMLElement $item
   *
   * @return bool|string
   */
  public function getImageUrl(SimpleXMLElement $item) {
    if ($this->configurations['has_image']) {
      $image_url = $item->xpath($this->configurations['image_xpath'])[0];
      if ($this->configurations['image_is_attribute']) {
        $image_url = (string) $image_url[$this->configurations['image_attribute']];
        return $image_url;
      }
      return (string) $image_url;
    }
    return FALSE;
  }

  /**
   * Get feed item terms based on defined feed entity configurations.
   *
   * @param \SimpleXMLElement $item
   * @param string $term_xpath
   *
   * @return array|boolean Array containing all terms found or false
   */
  public function getTerms(SimpleXMLElement $item, $term_xpath) {
    $terms = [];
    foreach ($item->xpath($term_xpath) as $term) {
      $terms[] = (string) $term;
    }
    return $terms;
  }

  /**
   * @param array $terms
   *
   * @return array
   */
  public function filterTerms(array $terms) {
    $terms = array_diff($terms, $this->removable_terms);
    $assoc_terms = [];
    foreach ($terms as $term) {
      $assoc_terms[$term] = $term;
    }
    foreach ($this->replaceable_terms as $term_key => $replacement_term_value) {
      if (isset($assoc_terms[$term_key])) {
        $assoc_terms[$term_key] = $replacement_term_value;
      }
    }
    $filtered_terms = array_merge($assoc_terms, $this->default_terms);
    return $filtered_terms;
  }

  /**
   * @param $default_term_string
   * @return array
   */
  public function getDefaultTerms($default_term_string) {
    $default_terms = explode(',', trim($default_term_string));
    $clean_default_terms = [];
    foreach ($default_terms as $default_term) {
      $default_term = trim($default_term);
      if (!empty($default_term)) {
        $clean_default_terms[$default_term] = $default_term;
      }
    }
    return $clean_default_terms;
  }

  /**
   * @param $removable_term_string
   * @return array
   */
  public function getRemovableTerms($removable_term_string) {
    $removable_term_array = explode(',', trim($removable_term_string));
    $clean_removable_terms = [];
    foreach ($removable_term_array as $term) {
      $clean_removable_terms[] = trim($term);
    }
    return $clean_removable_terms;
  }

  /**
   * @param string $replaceable_terms_unprocessed
   * @return array
   */
  public function getTermReplacements($replaceable_terms_unprocessed) {
    $replaceable_terms_unprocessed = explode(';', trim($replaceable_terms_unprocessed));
    $replaceable_terms = [];
    foreach ($replaceable_terms_unprocessed as $replacing_item) {
      if (!empty($replacing_item)) {
        $replaceable = explode('==', trim($replacing_item));
        if (count($replaceable) == 2) {
          $replaceable_terms[trim(reset($replaceable))] = trim(end($replaceable));
        }
        else {
          drupal_set_message('Could not properly process term item: ' . $replacing_item, 'warning');
        }
      }
    }
    return $replaceable_terms;
  }

}
