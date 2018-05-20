<?php

namespace Drupal\kuntaliitto_liftup\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * Class HideActivitiesForm.
 *
 * @package Drupal\kuntaliitto_liftup\Form
 */
class HideActivitiesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hide_activities_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Hide'),
      '#ajax' => [
        'callback' => '::removeCallback',
      ],
    ];

    return $form;
  }

  /**
   * Callback method for form submit.
   *
   * @param array $form
   *   Form representation.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Forms state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Response object.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand('.view--curent-activities', 'addClass', ['is-hidden']));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['hide_activities'] = TRUE;
  }

}
