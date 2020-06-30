<?php

namespace Drupal\search_api_mark_outdated;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Utility\Utility;

/**
 * Defines a class for reacting to search_api events.
 */
class SearchApiManager {

  /**
   * The Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new SearchApiOperations object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key-value store service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, StateInterface $state) {
    $this->entityTypeManager = $entity_type_manager;
    $this->state = $state;
  }

  /**
   * Act on entity update.
   *
   * @param array $indexes
   *   List of Search API Indexes.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that was just saved.
   *
   * @see hook_entity_update()
   */
  public function entityUpdate(array $indexes, EntityInterface $entity) {
    $ids = [];
    foreach (array_keys($entity->getTranslationLanguages()) as $langcode) {
      $ids[] = $this->createCombinedId($entity, $langcode);
    }
    foreach ($indexes as $index) {
      $this->setOutdated($index, $ids);
    }
  }

  /**
   * Act on recently indexed items.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   Search API Index.
   * @param string[] $item_ids
   *   Indexed entity ids.
   *
   * @see hook_search_api_items_indexed()
   */
  public function itemsIndexed(IndexInterface $index, array $item_ids) {
    $outdated_ids = $this->state->get('search_api_outdated_' . $index->id(), []);
    $outdated_ids = array_diff($outdated_ids, $item_ids);
    $this->state->set('search_api_outdated_' . $index->id(), $outdated_ids);
  }

  /**
   * Stores search_api combinedIds for outdated entities.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   Search API Index.
   * @param string[] $ids
   *   List of ids.
   */
  public function setOutdated(IndexInterface $index, array $ids) {

    $ids = $this->state->get('search_api_outdated_' . $index->id(), []) + $ids;
    $this->state->set('search_api_outdated_' . $index->id(), $ids);
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
    $outdated = array_flip($this->state->get('search_api_outdated_' . $index->id(), []));
    return isset($outdated[$id]);
  }

  /**
   * Return search_api combinedId for given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $langcode
   *   The language code of the translation to get or
   *   LanguageInterface::LANGCODE_DEFAULT
   *   to get the data in default language.
   *
   * @return string
   *   The combinedId.
   */
  public function createCombinedId(EntityInterface $entity, $langcode = NULL) {
    $datasource_id = 'entity:' . $entity->getEntityTypeId();
    if (!$langcode) {
      $langcode = $entity->language()->getId();
    }

    return Utility::createCombinedId($datasource_id, $entity->id() . ':' . $langcode);
  }

}
