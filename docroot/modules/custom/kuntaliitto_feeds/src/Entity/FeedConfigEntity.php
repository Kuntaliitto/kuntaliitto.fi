<?php

namespace Drupal\kuntaliitto_feeds\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Feed configuration entity.
 *
 * @ConfigEntityType(
 *   id = "feed_config_entity",
 *   label = @Translation("Feed configuration"),
 *   handlers = {
 *     "list_builder" = "Drupal\kuntaliitto_feeds\FeedConfigEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\kuntaliitto_feeds\Form\FeedConfigEntityForm",
 *       "edit" = "Drupal\kuntaliitto_feeds\Form\FeedConfigEntityForm",
 *       "delete" = "Drupal\kuntaliitto_feeds\Form\FeedConfigEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\kuntaliitto_feeds\FeedConfigEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "feed_config_entity",
 *   admin_permission = "administer feeds",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/feed_config_entity/{feed_config_entity}",
 *     "add-form" = "/admin/structure/feed_config_entity/add",
 *     "edit-form" = "/admin/structure/feed_config_entity/{feed_config_entity}/edit",
 *     "delete-form" = "/admin/structure/feed_config_entity/{feed_config_entity}/delete",
 *     "collection" = "/admin/structure/feed_config_entity"
 *   }
 * )
 */
class FeedConfigEntity extends ConfigEntityBase implements FeedConfigEntityInterface {

  /**
   * The Feed configuration ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Feed configuration label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Feed url.
   *
   * @var string
   */
  protected $url;

  /**
   * The Feed language.
   *
   * @var string
   */
  protected $feed_language;

  /**
   * The Feed status(enabled or disabled).
   *
   * @var bool
   */
  protected $feed_status;

  /**
   * The Feed default terms.
   *
   * @var string
   */
  protected $default_term;

  /**
   * Node that feed will be imported to.
   *
   * @var string
   */
  protected $import_node;


  /**
   * Determines if feed has wrapper.
   *
   * @var bool
   */
  protected $has_wrapper;

  /**
   * The Feed wrapper.
   *
   * @var string
   */
  protected $wrapper;

  /**
   * The Feed item xpath.
   *
   * @var string
   */
  protected $item_xpath;

  /**
   * The Feed title xpath.
   *
   * @var string
   */
  protected $title_xpath;

  /**
   * The Feed content format.
   *
   * @var string
   */
  protected $format;

  /**
   * The Feed content field.
   *
   * @var string
   */
  protected $content_field;

  /**
   * The Feed content xpath.
   *
   * @var string
   */
  protected $content_xpath;

  /**
   * Feed has terms.
   *
   * @var bool
   */
  protected $has_terms;

  /**
   * Feed term vocabulary.
   *
   * @var string
   */
  protected $vocabulary;

  /**
   * The Feed term field.
   *
   * @var string
   */
  protected $term_field;

  /**
   * The Feed term xpath.
   *
   * @var string
   */
  protected $term_xpath;

  /**
   * The Feed remove terms.
   *
   * @var string
   */
  protected $remove_terms;

  /**
   * The Feed replace terms.
   *
   * @var string
   */
  protected $replace_terms;

  /**
   * The Feed has image.
   *
   * @var bool
   */
  protected $has_image;

  /**
   * The Feed image field.
   *
   * @var string
   */
  protected $image_field;

  /**
   * The Feed image xpath.
   *
   * @var string
   */
  protected $image_xpath;

  /**
   * The Feed image is attribute.
   *
   * @var bool
   */
  protected $image_is_attribute;

  /**
   * The Feed image attribute.
   *
   * @var string
   */
  protected $image_attribute;

  /**
   * The Feed published status.
   *
   * @var bool
   */
  protected $published_status;

  /**
   * The Feed publish for.
   *
   * @var int
   */
  protected $publish_for;

}
