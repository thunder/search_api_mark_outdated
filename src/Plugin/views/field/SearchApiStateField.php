<?php

namespace Drupal\search_api_mark_outdated\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\views\field\SearchApiFieldTrait;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a bulk operation form element that works with entity browser.
 *
 * @ViewsField("search_api_mark_outdated_state_field")
 */
class SearchApiStateField extends FieldPluginBase {

  use SearchApiFieldTrait;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

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
    $field->setState($container->get('state'));
    $field->setEntityTypeManager($container->get('entity_type.manager'));

    return $field;
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
   * Set state service.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  protected function setState(StateInterface $state) {
    $this->state = $state;
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
   * Check if entity is outdated.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   Search API Index.
   * @param string $id
   *   Entity combined id.
   *
   * @return bool
   *   Entity is outdated.
   */
  public function isOutdated(IndexInterface $index, $id) {
    $outdated = array_flip($this->state->get('thunder_search_api_outdated_' . $index->id(), []));
    return isset($outdated[$id]);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    $element = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'data-is-outdated' => (int) $this->isOutdated($this->index, $row->search_api_id),
      ],
    ];

    if ($this->options['add_row_class']) {
      $element['#attached'] = [
        'library' => [
          'search_api_mark_outdated/mark_outdated',
        ],
      ];
    }

    return $element;
  }

}
