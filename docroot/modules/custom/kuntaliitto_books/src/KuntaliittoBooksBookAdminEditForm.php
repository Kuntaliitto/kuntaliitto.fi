<?php

namespace Drupal\kuntaliitto_books;

/**
 * Overrides Drupal\book\Form\BookAdminEditForm.
 *
 * Provides a form for administering a single book's hierarchy.
 */
use Drupal\book\Form\BookAdminEditForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Build the book tree.
 *
 * Extends Drupal\book\Form\BookAdminEditForm.
 */
class KuntaliittoBooksBookAdminEditForm extends BookAdminEditForm {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager->getStorage('node'),
      $container->get('kuntaliitto_books.book.manager')
    );
  }

  /**
   * Overrides buildForm, load the book tree for a whole book.
   *
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    if ($node && isset($node->book['bid'])) {
      $bookRoot = \Drupal::entityTypeManager()->getStorage('node')->load($node->book['bid']);
    }
    $form['#title'] = $bookRoot->label();
    $form['#node'] = $bookRoot;
    $this->bookAdminTable($bookRoot, $form);
    $form['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save book pages'),
    ];
    return $form;
  }

  /**
   * Helps build the main table in the book administration page form.
   *
   * @param array $tree
   *   A subtree of the book menu hierarchy.
   * @param array $form
   *   The form that is being modified, passed by reference.
   *
   * @see self::buildForm()
   */
  protected function bookAdminTableTree(array $tree, array &$form) {
    // The delta must be big enough to give each node a distinct value.
    $count = count($tree);
    $delta = ($count < 30) ? 15 : intval($count / 2) + 1;

    $access = \Drupal::currentUser()->hasPermission('administer nodes');
    $destination = $this->getDestinationArray();

    foreach ($tree as $key => $data) {
      $nid = $data['link']['nid'];
      $id = 'book-admin-' . $nid;
      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      if ($node->isTranslatable()) {
        if ($node->hasTranslation($language)) {
          $data['language'] = $language;
        }
      }
      $form[$id]['#item'] = $data['link'];
      $form[$id]['#nid'] = $nid;
      $form[$id]['#attributes']['class'][] = 'draggable';
      $form[$id]['#weight'] = $data['link']['weight'];

      if (isset($data['link']['depth']) && $data['link']['depth'] > 2) {
        $indentation = [
          '#theme' => 'indentation',
          '#size' => $data['link']['depth'] - 2,
        ];
      }

      $state = $data['link']['status'] == '1' ? t('Published') : t('Unpublished');
      $state .= isset($data['language']) ? ', ' . $data['language'] : ', <span class="color-error">' . t('not translated') . '</span>';
      $form[$id]['title'] = [
        '#prefix' => !empty($indentation) ? drupal_render($indentation) : '',
        '#type' => 'textfield',
        '#title' => $state,
        '#default_value' => $data['link']['title'],
        '#maxlength' => 255,
        '#size' => 40,
        '#attributes' => [
          'class' => ['book-tree-label'],
        ],
      ];

      $form[$id]['weight'] = [
        '#type' => 'weight',
        '#default_value' => $data['link']['weight'],
        '#delta' => max($delta, abs($data['link']['weight'])),
        '#title' => $this->t('Weight for @title', ['@title' => $data['link']['title']]),
        '#title_display' => 'invisible',
        '#attributes' => [
          'class' => ['book-weight'],
        ],
      ];

      $form[$id]['parent']['nid'] = [
        '#parents' => ['table', $id, 'nid'],
        '#type' => 'hidden',
        '#value' => $nid,
        '#attributes' => [
          'class' => ['book-nid'],
        ],
      ];

      $form[$id]['parent']['pid'] = [
        '#parents' => ['table', $id, 'pid'],
        '#type' => 'hidden',
        '#default_value' => $data['link']['pid'],
        '#attributes' => [
          'class' => ['book-pid'],
        ],
      ];

      $form[$id]['parent']['bid'] = [
        '#parents' => ['table', $id, 'bid'],
        '#type' => 'hidden',
        '#default_value' => $data['link']['bid'],
        '#attributes' => [
          'class' => ['book-bid'],
        ],
      ];

      $form[$id]['operations'] = [
        '#type' => 'operations',
      ];
      $form[$id]['operations']['#links']['view'] = [
        'title' => $this->t('View'),
        'url' => new Url('entity.node.canonical', ['node' => $nid]),
      ];

      if ($access) {
        $form[$id]['operations']['#links']['edit'] = [
          'title' => $this->t('Edit'),
          'url' => new Url('entity.node.edit_form', ['node' => $nid]),
          'query' => $destination,
        ];
        $form[$id]['operations']['#links']['delete'] = [
          'title' => $this->t('Delete'),
          'url' => new Url('entity.node.delete_form', ['node' => $nid]),
          'query' => $destination,
        ];
      }

      if ($data['below']) {
        $this->bookAdminTableTree($data['below'], $form);
      }
    }
  }

}
