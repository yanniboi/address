<?php

/**
 * @file
 * Contains \Drupal\address\SubdivisionStorage.
 */

namespace Drupal\address;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Utility\String;

/**
 * Storage for subdivision storage records.
 *
 * Storage records of other config entities map 1-1 to config objects.
 * Since there are too many subdivisions (> 12 000), creating a config object
 * for each one would be impractical. Instead, their storage records are stored
 * grouped by parent ID. For example, 'US_CA' and 'US_DC' are both stored in
 * the address.subdivisions.US config object. This reduces the number
 * of needed config objects significantly (to around 520).
 */
class SubdivisionRecordStorage implements SubdivisionRecordStorageInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a SubdivisionRecordStorage object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * Gets the root key of the config object.
   *
   * A root key is needed because core doesn't support having a sequence at
   * the root level (#2248709).
   *
   * @return string
   *   The root key.
   */
  protected function getRootKey() {
    return 'subdivisions';
  }

  /**
   * Returns the prefix used to create the config name.
   *
   * @return string
   *   The prefix.
   */
  protected function getPrefix() {
    return 'address.subdivisions.';
  }

  /**
   * Gets the name of the config object for the provided ID.
   *
   * The config object name is constructed from the prefix and the parent ID.
   * The parent ID is constructed by taking n-1 segments of the original ID.
   * E.g. for "BR_AL_64b095" the parent ID is "BR_AL", and the config name
   * is "address.subdivisions.BR_AL".
   *
   * @param string $id
   *   The ID.
   *
   * @return string
   *   The config name, or NULL if the provided ID is malformed.
   */
  protected function getConfigName($id) {
    $parentId = NULL;
    $idParts = explode('_', $id);
    if (count($idParts) > 1) {
      array_pop($idParts);
      $parentId = $this->getPrefix() . implode('_', $idParts);
    }

    return $parentId;
  }

  /**
   * Gets the names of the config objects for the provided IDs.
   *
   * @param array $ids
   *   The IDs.
   *
   * @return array
   *   An array in the $name => $ids format.
   */
  protected function getConfigNames($ids) {
    $names = array();
    foreach ($ids as $id) {
      // Gather the needed config names. Ignore any malformed id.
      $name = $this->getConfigName($id);
      if ($name) {
        $names[$name][] = $id;
      }
    }

    return $names;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids, $overrideFree = FALSE) {
    $names = $this->getConfigNames($ids);
    $rootKey = $this->getRootKey();
    $records = array();
    foreach ($this->configFactory->loadMultiple(array_keys($names)) as $config) {
      $data = $overrideFree ? $config->getOriginal($rootKey, FALSE) : $config->get($rootKey);
      $loadedIds = array_keys($data);
      $neededIds = array_intersect($ids, $loadedIds);
      $records += array_intersect_key($data, array_flip($neededIds));
    }

    return $records;
  }

  /**
   * {@inheritdoc}
   */
  public function loadChildren(array $parentIds, $overrideFree = FALSE) {
    $prefix = $this->getPrefix();
    $names = array();
    foreach ($parentIds as $parentId) {
      $names[] = $prefix . $parentId;
    }
    $rootKey = $this->getRootKey();
    $records = array();
    foreach ($this->configFactory->loadMultiple($names) as $config) {
      $records += $overrideFree ? $config->getOriginal($rootKey, FALSE) : $config->get($rootKey);
    }

    return $records;
  }

  /**
   * {@inheritdoc}
   */
  public function exists($id) {
    $name = $this->getConfigName($id);
    if (!$name) {
      // Malformed id.
      return FALSE;
    }
    $configs = $this->configFactory->loadMultiple(array($name));
    if (empty($configs)) {
      return FALSE;
    }
    $config = reset($configs);
    $data = $config->get($rootKey . '.' . $id);

    return !empty($data);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $ids) {
    $rootKey = $this->getRootKey();
    foreach ($this->getConfigNames($ids) as $name => $groupedIds) {
      $config = $this->configFactory->getEditable($name);
      foreach ($groupedIds as $id) {
        $config->clear($rootKey . '.' . $id);
      }

      // Delete the config object if it contains no other records.
      $data = $config->get($rootKey);
      if (empty($data)) {
        $config->delete();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save($id, array $record) {
    // Make sure that the ID can be mapped to a config name.
    $name = $this->getConfigName($id);
    if (!$name) {
      throw new \InvalidArgumentException(String::format('The subdivision ID "@id" is malformed.', array('@id' => $id)));
    }

    $rootKey = $this->getRootKey();
    $config = $this->configFactory->getEditable($name);
    $config->set($rootKey . '.' . $id, $record);
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  public function saveMultiple(array $records) {
    $ids = array_keys($records);
    $rootKey = $this->getRootKey();
    foreach ($this->getConfigNames($ids) as $name => $groupedIds) {
      $config = $this->configFactory->getEditable($name);
      foreach ($groupedIds as $id) {
        $config->set($rootKey . '.' . $id, $records[$id]);
      }
      $config->save();
    }
  }

}
