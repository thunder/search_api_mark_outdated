<?php

namespace Drupal\search_api_mark_outdated\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Plugin\views\field\SearchApiFieldTrait;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\search_api_mark_outdated\SearchApiManager;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A field that indicates if a search result item might be outdated.
 *
 * @ViewsField("search_api_mark_outdated_state_field")
 */
class SearchApiStateField extends FieldPluginBase {

  use SearchApiFieldTrait;

  /**
   * The search api mark outdated manager.
   *
   * @var \Drupal\search_api_mark_outdated\SearchApiManager
   */
  protected $searchApiManager;

  /**
   * The search index.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $field = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $field->setSearchApiManager($container->get('search_api_mark_outdated.manager'));
    $field->setEntityTypeManager($container->get('entity_type.manager'));

    return $field;
  }

  /**
   * Set the search api mark outdated manager.
   *
   * @param \Drupal\search_api_mark_outdated\SearchApiManager $searchApiManager
   *   The manager service.
   */
  protected function setSearchApiManager(SearchApiManager $searchApiManager) {
    $this->searchApiManager = $searchApiManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['label']['default'] = '';
    $options['add_row_class']['default'] = TRUE;

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['add_row_class'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add row class'),
      '#default_value' => $this->options['add_row_class'],
      '#description' => $this->t('Add a row class to indicate that the result item is outdated.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $base_table = $view->storage->get('base_table');
    $this->index = SearchApiQuery::getIndexFromTable($base_table, $this->getEntityTypeManager());
    if (!$this->index) {
      $view_label = $view->storage->label();
      throw new \InvalidArgumentException("View '$view_label' is not based on Search API but tries to use its row plugin.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    $element = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'data-is-outdated' => (int) $this->searchApiManager->isOutdated($this->index, $row->search_api_id),
      ],
    ];

    if ($this->options['add_row_class']) {
      $element['#attached'] = [
        'library' => [
          'search_api_mark_outdated/row-class',
        ],
      ];
    }

    return $element;
  }

}
