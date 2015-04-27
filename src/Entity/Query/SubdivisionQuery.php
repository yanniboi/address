<?php

/**
 * @file
 * Contains \Drupal\address\Entity\Query\SubdivisionQuery.
 */

namespace Drupal\address\Entity\Query;

use Drupal\address\SubdivisionRecordStorageInterface;
use Drupal\Core\Config\Entity\Query\Query as ConfigQuery;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the entity query for subdivision entities.
 */
class SubdivisionQuery extends ConfigQuery {

  /**
   * The record storage.
   *
   * @var \Drupal\address\SubdivisionRecordStorageInterface
   */
  protected $recordStorage;

  /**
   * Constructs a SubdivisionQuery object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   The entity type definition.
   * @param string $conjunction
   *   - AND: all of the conditions on the query need to match.
   *   - OR: at least one of the conditions on the query need to match.
   * @param \Drupal\address\SubdivisionRecordStorageInterface $recordStorage
   *   The record storage.
   * @param array $namespaces
   *   List of potential namespaces of the classes belonging to this query.
   */
  function __construct(EntityTypeInterface $entityType, $conjunction, SubdivisionRecordStorageInterface $recordStorage, array $namespaces) {
    $this->recordStorage = $recordStorage;
    // Copy of QueryBase::__construct(), since we can't call the parent
    // __construct() because ConfigQuery needs the ConfigFactory param.
    $this->entityTypeId = $entityType->id();
    $this->entityType = $entityType;
    $this->conjunction = $conjunction;
    $this->namespaces = $namespaces;
    $this->condition = $this->conditionGroupFactory($conjunction);
  }

  /**
   * {@inheritdoc}
   */
  protected function loadRecords() {
    // There are too many subdivisions to load at once, so the query must
    // be restricted by a condition on id, parentId or countryCode.
    $ids = $this->getConditionValues('id');
    if ($ids) {
      return $this->recordStorage->loadMultiple($ids);
    }
    $parentIds = $this->getConditionValues('parentId');
    if ($parentIds) {
      return $this->recordStorage->loadChildren($parentIds);
    }
    $countryCodes = $this->getConditionValues('countryCode');
    if ($countryCodes) {
      return $this->recordStorage->loadChildren($countryCodes);
    }

    throw new \RuntimeException('The subdivision query must have a condition on id, parentId, or countryCode.');
  }

  /**
   * Gets all condition values for the provided key.
   *
   * @param string $key
   *   The key.
   *
   * @return array
   *   An array of values.
   */
  protected function getConditionValues($key) {
    if ($this->condition->getConjunction() != 'AND') {
      return array();
    }

    $values = array();
    foreach ($this->condition->conditions() as $condition) {
      if (is_string($condition['field']) && $condition['field'] == $key) {
        $operator = $condition['operator'] ?: (is_array($condition['value']) ? 'IN' : '=');
        if ($operator == '=') {
          $values = array($condition['value']);
          break;
        }
        elseif ($operator == 'IN') {
          $values = $condition['value'];
          break;
        }
      }
    }

    return $values;
  }

}
