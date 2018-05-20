<?php

namespace Drupal\kuntaliitto_feeds\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\kuntaliitto_feeds\XmlParserInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FeedConfigEntityForm.
 *
 * @package Drupal\kuntaliitto_feeds\Form
 */
class FeedConfigEntityForm extends EntityForm {

  /**
   * EntityTypeManager used to get entity storage.
   *
   * @protected Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  protected $entityTypeManager;

  /**
   * EntityTypeManager used to get entity storage.
   *
   * @protected Drupal\kuntaliitto_feeds\XmlParserInterface $xmlParser
   */
  protected $xmlParser;

  /**
   * FeedConfigEntityForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\kuntaliitto_feeds\XmlParserInterface $xmlParser
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, XmlParserInterface $xmlParser) {
    $this->entityTypeManager = $entityTypeManager;
    $this->xmlParser = $xmlParser;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('kuntaliitto_feeds.xml_parser')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\kuntaliitto_feeds\Entity\FeedConfigEntity $feed_config_entity */
    $feed_config_entity = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $feed_config_entity->label(),
      '#description' => $this->t("Label for the Feed configuration."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $feed_config_entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\kuntaliitto_feeds\Entity\FeedConfigEntity::load',
      ],
      '#disabled' => !$feed_config_entity->isNew(),
    ];

    $form['url'] = [
      '#type' => 'url',
      '#title' => $this->t('Feed url'),
      '#size' => 30,
      '#description' => $this->t('Example: <i>http://kuntalehti.fi/feed/</i>'),
      '#required' => TRUE,
      '#default_value' => $feed_config_entity->get('url'),
    ];

    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $options = [];
    /** @var \Drupal\node\Entity\NodeType $node_type */
    foreach ($node_types as $node_type) {
      $options[$node_type->id()] = $node_type->label();
    }

    $form['import_node'] = [
      '#type' => 'select',
      '#title' => $this->t('Destination node'),
      '#options' => $options,
      '#default_value' => $feed_config_entity->get('import_node'),
    ];

    $form['content_source'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Content source'),
      '#maxlength' => 255,
      '#default_value' => $feed_config_entity->get('content_source'),
      '#description' => $this->t("Term of content source"),
      '#required' => TRUE,
    ];

    $form['feed_language'] = [
      '#type' => 'language_select',
      '#title' => $this->t('Feed language'),
      '#description' => $this->t('Language of feed, to set on destination content.'),
      '#default_value' => $feed_config_entity->get('feed_language'),
    ];

    $form['feed_status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Feed status'),
      '#options' => [1 => $this->t('Enabled'), 0 => $this->t('Disabled')],
      '#default_value' => $feed_config_entity->get('feed_status') ? 1 : 0,
    ];

    $form['has_wrapper'] = [
      '#type' => 'radios',
      '#title' => $this->t('Does feed has wrapper?'),
      '#options' => [1 => $this->t('Yes'), 0 => $this->t('No')],
      '#default_value' => $feed_config_entity->get('has_wrapper') ? 1 : 0,
    ];

    $form['wrapper'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wrapper element name'),
      '#maxlength' => 255,
      '#default_value' => $feed_config_entity->get('wrapper'),
      '#states' => [
        'visible' => [
          ':input[name="has_wrapper"]' => ['value' => 1],
        ],
        'required' => [
          ':input[name="has_wrapper"]' => ['value' => 1],
        ],
      ],
    ];

    $form['item_xpath'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Item XPath'),
      '#maxlength' => 255,
      '#default_value' => $feed_config_entity->get('item_xpath'),
      '#description' => $this->t("Xpath of feed item"),
      '#required' => TRUE,
    ];

    $form['link'] = [
      '#type' => 'details',
      '#title' => $this->t('Link'),
      '#open' => TRUE,
    ];

    $form['link']['link_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link field'),
      '#maxlength' => 255,
      '#default_value' => $feed_config_entity->get('link_field'),
      '#description' => $this->t("Link field machine name."),
      '#required' => TRUE,
    ];

    $form['link']['link_xpath'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link XPath'),
      '#maxlength' => 255,
      '#default_value' => $feed_config_entity->get('link_xpath'),
      '#description' => $this->t("Link xpath on feed item."),
      '#required' => TRUE,
    ];

    $form['title'] = [
      '#type' => 'details',
      '#title' => $this->t('Title'),
      '#open' => TRUE,
    ];

    $form['title']['title_xpath'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title XPath'),
      '#maxlength' => 255,
      '#default_value' => $feed_config_entity->get('title_xpath'),
      '#description' => $this->t("Title xpath on feed item."),
      '#required' => TRUE,
    ];

    $form['content'] = [
      '#type' => 'details',
      '#title' => $this->t('Content'),
      '#open' => TRUE,
    ];

    $format_types = filter_formats();
    $options = [];
    /** @var \Drupal\filter\Entity\FilterFormat $format_type */
    foreach ($format_types as $format_type) {
      $options[$format_type->id()] = $format_type->label();
    }

    $form['content']['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Content format'),
      '#options' => $options,
      '#default_value' => $feed_config_entity->get('format'),
    ];

    $form['content']['content_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Content field'),
      '#maxlength' => 255,
      '#default_value' => $feed_config_entity->get('content_field'),
      '#description' => $this->t("Content field machine name."),
      '#required' => TRUE,
    ];

    $form['content']['content_xpath'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Content XPath'),
      '#maxlength' => 255,
      '#default_value' => $feed_config_entity->get('content_xpath'),
      '#description' => $this->t("Content xpath on feed item."),
      '#required' => TRUE,
    ];

    $form['terms'] = [
      '#type' => 'details',
      '#title' => $this->t('Taxonomy terms'),
      '#open' => TRUE,
    ];

    $form['terms']['has_terms'] = [
      '#type' => 'radios',
      '#title' => $this->t('Source has term field'),
      '#options' => [1 => $this->t('Yes'), 0 => $this->t('No')],
      '#description' => $this->t("You can still add default terms if source has no terms."),
      '#default_value' => $feed_config_entity->get('has_terms') ? 1 : 0,
    ];

    $vocabulary_types = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple();
    $options = [];
    /** @var \Drupal\taxonomy\Entity\Term $vocabulary_type */
    foreach ($vocabulary_types as $vocabulary_type) {
      $options[$vocabulary_type->id()] = $vocabulary_type->label();
    }

    $form['terms']['vocabulary'] = [
      '#type' => 'select',
      '#title' => $this->t('Term vocabulary'),
      '#options' => $options,
      '#default_value' => $feed_config_entity->get('vocabulary'),
    ];

    $form['terms']['term_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Destination term field'),
      '#maxlength' => 255,
      '#default_value' => $feed_config_entity->get('term_field'),
      '#description' => $this->t("Term field machine name."),
      '#states' => [
        'required' => [
          ':input[name="has_terms"]' => ['value' => 1],
        ],
      ],
    ];

    $form['terms']['term_xpath'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Term XPath'),
      '#maxlength' => 255,
      '#default_value' => $feed_config_entity->get('term_xpath'),
      '#description' => $this->t("Term xpath on feed item."),
      '#states' => [
        'visible' => [
          ':input[name="has_terms"]' => ['value' => 1],
        ],
        'required' => [
          ':input[name="has_terms"]' => ['value' => 1],
        ],
      ],
    ];

    $form['terms']['default_term'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Feed default terms'),
      '#maxlength' => 255,
      '#description' => $this->t("Category to add to feeds separated by comma."),
      '#default_value' => $feed_config_entity->get('default_term'),
    ];

    $form['terms']['remove_terms'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Feed terms to remove'),
      '#description' => $this->t("Categories to remove from feeds separated by comma."),
      '#default_value' => $feed_config_entity->get('remove_terms'),
    ];

    $form['terms']['replace_terms'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Feed terms to replace'),
      '#description' => $this->t("Categories to replace separated by semicolon hinted with two equal signs. Example: Law == Local government; politic==government;"),
      '#default_value' => $feed_config_entity->get('replace_terms'),
    ];

    $form['image'] = [
      '#type' => 'details',
      '#title' => $this->t('Image'),
      '#open' => TRUE,
    ];

    $form['image']['has_image'] = [
      '#type' => 'radios',
      '#title' => $this->t('Source has image field'),
      '#options' => [1 => $this->t('Yes'), 0 => $this->t('No')],
      '#default_value' => $feed_config_entity->get('has_image') ? 1 : 0,
    ];

    $form['image']['image_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Destination image field'),
      '#maxlength' => 255,
      '#default_value' => $feed_config_entity->get('image_field'),
      '#description' => $this->t("Image field machine name."),
      '#states' => [
        'visible' => [
          ':input[name="has_image"]' => ['value' => 1],
        ],
        'required' => [
          ':input[name="has_image"]' => ['value' => 1],
        ],
      ],
    ];

    $form['image']['image_xpath'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image XPath'),
      '#maxlength' => 255,
      '#default_value' => $feed_config_entity->get('image_xpath'),
      '#description' => $this->t("Image xpath on feed item."),
      '#states' => [
        'visible' => [
          ':input[name="has_image"]' => ['value' => 1],
        ],
        'required' => [
          ':input[name="has_image"]' => ['value' => 1],
        ],
      ],
    ];

    $form['image']['image_is_attribute'] = [
      '#type' => 'radios',
      '#title' => $this->t('Image url is set in tags attribute'),
      '#options' => [1 => $this->t('Yes'), 0 => $this->t('No')],
      '#default_value' => $feed_config_entity->get('image_is_attribute') ? 1 : 0,
      '#states' => [
        'visible' => [
          ':input[name="has_image"]' => ['value' => 1],
        ],
        'required' => [
          ':input[name="has_image"]' => ['value' => 1],
        ],
      ],
    ];

    $form['image']['image_attribute'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image attribute'),
      '#maxlength' => 255,
      '#default_value' => $feed_config_entity->get('image_attribute'),
      '#description' => $this->t("Image attribute name on feed item."),
      '#states' => [
        'visible' => [
          ':input[name="image_is_attribute"]' => ['value' => 1],
          ':input[name="has_image"]' => ['value' => 1],
        ],
        'required' => [
          ':input[name="image_is_attribute"]' => ['value' => 1],
          ':input[name="has_image"]' => ['value' => 1],
        ],
      ],
    ];

    $form['publishing'] = [
      '#type' => 'details',
      '#title' => $this->t('Publishing'),
      '#open' => TRUE,
    ];

    $form['publishing']['published_xpath'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publishing date XPath'),
      '#maxlength' => 255,
      '#default_value' => $feed_config_entity->get('published_xpath'),
      '#description' => $this->t("Publishing date xpath on feed item."),
    ];

    $form['publishing']['published_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publishing date format'),
      '#maxlength' => 255,
      '#default_value' => $feed_config_entity->get('published_format'),
      '#description' => $this->t("Format must be put in in <a href='http://php.net/manual/en/function.date.php#refsect1-function.date-parameters'> PHP format</a>"),
      '#states' => [
        'visible' => [
          ':input[name="published_xpath"]' => ['filled' => TRUE],
        ],
        'required' => [
          ':input[name="published_xpath"]' => ['filled' => TRUE],
        ],
      ],
    ];

    $form['publishing']['published_status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Published by default'),
      '#options' => [1 => $this->t('Yes'), 0 => $this->t('No')],
      '#default_value' => $feed_config_entity->get('published_status') ? 1 : 0,
    ];

    $form['publishing']['publish_for'] = [
      '#type' => 'number',
      '#title' => $this->t('Published time'),
      '#description' => $this->t("Time in days of how long these feed items will be published."),
      '#default_value' => $feed_config_entity->get('publish_for'),
      '#states' => [
        'visible' => [
          ':input[name="published_status"]' => ['value' => 1],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\kuntaliitto_feeds\Entity\FeedConfigEntity $feed_config_entity */
    $feed_config_entity = $this->entity;
    $values = $form_state->getValues();
    foreach ($values as $value_key => $value) {
      $feed_config_entity->set($value_key, $value);
    }
    $status = $feed_config_entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Feed configuration.', [
          '%label' => $feed_config_entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Feed configuration.', [
          '%label' => $feed_config_entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($feed_config_entity->urlInfo('collection'));
  }

}
