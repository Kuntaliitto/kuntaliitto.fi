<?php

namespace Drupal\kuntaliitto_common\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;

/**
 * Plugin implementation of the 'kuntaliitto term label' formatter.
 *
 * @FieldFormatter(
 *   id = "kuntaliitto_term_label",
 *   label = @Translation("Kuntaliitto term"),
 *   description = @Translation("Display the label of the kuntaliitto term entities."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class KuntaliittoTermsFieldFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->getSetting('link') ? t('Link to the referenced entity') : t('No link');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $output_as_link = $this->getSetting('link');

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $label = $entity->label();
      // If the link is to be displayed and the entity has a uri, display a
      // link.
      if ($output_as_link && !$entity->isNew()) {
        try {
          $uri = $entity->urlInfo();
        }
        catch (UndefinedLinkTemplateException $e) {
          $output_as_link = FALSE;
        }
      }

      if ($output_as_link && isset($uri) && !$entity->isNew()) {

        /** @var \Drupal\taxonomy\Entity\Term $entity */
        if ($entity = $uri->getOptions()['entity']) {
          $vocabulary = $entity->getVocabularyId();
          if ($vocabulary == 'content_types') {
            switch ($entity->id()) {
              case (0):
                $uri = Url::fromRoute('entity.node.canonical', ['node' => '0']);
                break;

              default:
                $uri = Url::fromRoute('view.solr_search.page_solr_search', [
                  'f' => [$vocabulary . ':' . $entity->id()],
                  's' => '',
                ]);

            }
          }
          elseif ($vocabulary == 'municipalities') {
          }
          elseif ($vocabulary == 'bloggers') {
            $uri = Url::fromRoute('view.taxonomy_term.page_1', [$entity->id()]);
          }
          else {
            $uri = Url::fromRoute('view.solr_search.page_solr_search', [
              'f' => [$vocabulary . ':' . $entity->id()],
              's' => '',
            ]);
          }
        }

        $elements[$delta] = [
          '#type' => 'link',
          '#title' => $label,
          '#url' => $uri,
          '#options' => $uri->getOptions(),
        ];

        if (!empty($items[$delta]->_attributes)) {
          $elements[$delta]['#options'] += ['attributes' => []];
          $elements[$delta]['#options']['attributes'] += $items[$delta]->_attributes;
          unset($items[$delta]->_attributes);
        }
      }
      else {
        $elements[$delta] = ['#plain_text' => $label];
      }
      $elements[$delta]['#cache']['tags'] = $entity->getCacheTags();
    }

    return $elements;
  }

}
