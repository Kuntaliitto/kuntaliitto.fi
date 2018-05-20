<?php

namespace Drupal\kuntaliitto_feeds\Form;

use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\kuntaliitto_feeds\XmlParserInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Feed Import form class.
 *
 * @ingroup kuntaliitto_feed
 */
class FeedGetForm extends FormBase {

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
   * EntityTypeManager used to get entity storage.
   *
   * @protected Drupal\Core\Entity\Query\QueryFactory $entityQuery
   */
  protected $entityQuery;

  /**
   * FeedConfigEntityForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\kuntaliitto_feeds\XmlParserInterface $xmlParser
   * @param \Drupal\Core\Entity\Query\QueryFactory $entityQuery
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, XmlParserInterface $xmlParser, QueryFactory $entityQuery) {
    $this->entityTypeManager = $entityTypeManager;
    $this->xmlParser = $xmlParser;
    $this->entityQuery = $entityQuery;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('kuntaliitto_feeds.xml_parser'),
      $container->get('entity.query')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'kuntaliitto_feed_import';
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Load all configuration entities that are enabled and collect entity labels.
    $entity_ids = $this->entityQuery
      ->get('feed_config_entity')
      ->execute();
    $entities = $this->entityTypeManager
      ->getStorage('feed_config_entity')
      ->loadMultiple($entity_ids);
    $list_items = [];
    foreach ($entities as $entity) {
      $list_items[$entity->id()] = $entity->label();
    }

    $form['feed_config_ids'] = [
      '#type' => 'checkboxes',
      '#options' => $list_items,
      '#title' => $this->t('Feeds to import'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import feeds'),
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('entity.feed_config_entity.collection'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (count(array_filter($form_state->getValue('feed_config_ids'))) == 0) {
      $form_state->setError($form['feed_config_ids'], $this->t('You need to choose at least one feed source to import.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->xmlParser->process(array_filter($form_state->getValue('feed_config_ids')));
  }

}
