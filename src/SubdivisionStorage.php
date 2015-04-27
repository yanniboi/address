<?php

/**
 * @file
 * Contains \Drupal\address\SubdivisionStorage.
 */

namespace Drupal\address;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Storage for subdivision entities.
 *
 * Instead of storing each subdivision in its own config object, it relies
 * on SubdivisionRecordStorage to store subdivisions grouped by parent ID.
 *
 * @see \Drupal\address\SubdivisionRecordStorage
 */
class SubdivisionStorage extends ConfigEntityStorage implements SubdivisionStorageInterface {

  /**
   * The record storage.
   *
   * @var \Drupal\address\SubdivisionRecordStorageInterface
   */
  protected $recordStorage;

  /**
   * Constructs a SubdivisionStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   The entity type definition.
   * @param \Drupal\address\SubdivisionRecordStorageInterface $recordStorage
   *   The record storage.
   * @param \Drupal\Component\Uuid\UuidInterface $uuidService
   *   The UUID service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(EntityTypeInterface $entityType, SubdivisionRecordStorageInterface $recordStorage, ConfigFactoryInterface $configFactory, UuidInterface $uuidService, LanguageManagerInterface $languageManager) {
    parent::__construct($entityType, $configFactory, $uuidService, $languageManager);

    $this->recordStorage = $recordStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entityType) {
    return new static(
      $entityType,
      $container->get('address.subdivision_record_storage'),
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getIDFromConfigName($name, $configPrefix) {
    // Importing individual entities via config is not possible because
    // they are not stored in individual config objects.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getQueryServiceName() {
    return 'address.subdivision_query';
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $ids = NULL) {
    if ($ids === NULL) {
      // There are too many entities to list at once.
      return array();
    }
    $records = $this->recordStorage->loadMultiple($ids, $this->overrideFree);

    return $this->mapFromStorageRecords($records);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
    if ($entities) {
      // Perform the actual deletion.
      parent::delete($entities);

      // Remove any children.
      $this->deleteChildren($entities);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteChildren(array $entities) {
    $ids = $this->getEntityIds($entities);
    $records = $this->recordStorage->loadChildren($ids, $this->overrideFree);
    if ($records) {
      // Done on the entity level and not on the record level to ensure the
      // firing of the appropriate hooks.
      $children = $this->loadMultiple(array_keys($records));
      $this->delete($children);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {
    $ids = $this->getEntityIds($entities);
    $this->recordStorage->delete($ids);
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
    if ($id !== $entity->id()) {
      // We don't care about supporting id changes.
      throw new \Exception('Changing the id of subdivision entities is not supported.');
    }

    $record = $this->mapToStorageRecord($entity);
    $this->recordStorage->save($id, $record);

    return $entity->isNew() ? SAVED_NEW : SAVED_UPDATED;
  }

  /**
   * {@inheritdoc}
   */
  protected function has($id, EntityInterface $entity) {
    return $this->recordStorage->exists($id);
  }

  /**
   * Gets the IDs of the provided entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   The entities.
   *
   * @return array
   *   The IDs.
   */
  protected function getEntityIds(array $entities) {
    $ids = array_map(function($entity) {
      return $entity->id();
    }, $entities);

    return $ids;
  }

}
