<?php

/**
 * @file
 * Contains \Drupal\address\SubdivisionStorageInterface.
 */

namespace Drupal\address;

use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the interface for subdivision storage classes.
 */
interface SubdivisionStorageInterface extends EntityStorageInterface {

  /**
   * Deletes all child entities of the provided entities.
   *
   * Note that for top-level subdivisions such as "US_CA" the parent entity
   * is an address format ("US").
   *
   * @param array $entities
   *   An array of entity objects whose children should be deleted.
   */
  public function deleteChildren(array $entities);

}
