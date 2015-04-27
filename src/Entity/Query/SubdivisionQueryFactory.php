<?php

/**
 * @file
 * Contains \Drupal\address\Entity\Query\SubdivisionQueryFactory.
 */

namespace Drupal\address\Entity\Query;

use Drupal\address\SubdivisionRecordStorageInterface;
use Drupal\Core\Config\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryBase;

/**
 * Provides a factory for creating subdivision query objects.
 */
class SubdivisionQueryFactory extends QueryFactory {

  /**
   * The record storage.
   *
   * @var \Drupal\address\SubdivisionRecordStorageInterface
   */
  protected $recordStorage;

  /**
   * Constructs a SubdivisionQueryFactory object.
   *
   * @param \Drupal\address\SubdivisionRecordStorageInterface $recordStorage
   *   The record storage used by the subdivision query.
   */
  public function __construct(SubdivisionRecordStorageInterface $recordStorage) {
    $this->recordStorage = $recordStorage;
    $this->namespaces = QueryBase::getNamespaces($this);
  }

  /**
   * {@inheritdoc}
   */
  public function get(EntityTypeInterface $entityType, $conjunction) {
    return new SubdivisionQuery($entityType, $conjunction, $this->recordStorage, $this->namespaces);
  }

}
