<?php

namespace Drupal\kuntaliitto_views_manager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class NewsroomConfigForm.
 *
 * @package Drupal\kuntaliitto_views_manager\Form
 */
class NewsroomConfigForm extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
      EntityTypeManager $entity_type_manager
    ) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
            $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'kuntaliitto_views_manager.newsroomconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'newsroom_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('kuntaliitto_views_manager.newsroomconfig');
    $displays = $this->entityTypeManager
      ->getStorage('view')
      ->load('newsroom')
      ->get('display');
    unset($displays['default']);

    foreach ($displays as $display => $data) {
      $form[$display] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'node',
        '#title' => $data['display_title'],
        '#description' => 'Node on which ' . $data['display_title'] . ' will be rendered.',
        '#default_value' => $config->get($display) ?
        $this->entityTypeManager->getStorage('node')->load($config->get($display)) : NULL,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $values = $form_state->getValues();
    $config = $this->config('kuntaliitto_views_manager.newsroomconfig');
    foreach ($values as $value_key => $value) {
      $config->set($value_key, $value);
    }
    $status = $config->save();
  }

}
