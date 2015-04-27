<?php

/**
 * @file
 * Contains \Drupal\address\Entity\Subdivision.
 */

namespace Drupal\address\Entity;

use CommerceGuys\Addressing\Model\SubdivisionInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Subdivision configuration entity.
 *
 * @ConfigEntityType(
 *   id = "subdivision",
 *   label = @Translation("Subdivision"),
 *   handlers = {
 *     "storage" = "Drupal\address\SubdivisionStorage",
 *     "list_builder" = "Drupal\address\SubdivisionListBuilder",
 *     "form" = {
 *       "add" = "Drupal\address\Form\SubdivisionForm",
 *       "edit" = "Drupal\address\Form\SubdivisionForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer subdivisions",
 *   config_prefix = "subdivision",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/config/regional/subdivisions/{address_format}/{parent}",
 *     "edit-form" = "/admin/config/regional/subdivisions/manage/{subdivision}",
 *     "delete-form" = "/admin/config/regional/subdivisions/manage/{subdivision}/delete"
 *   }
 * )
 */
class Subdivision extends ConfigEntityBase implements SubdivisionInterface {

  /**
   * The country code.
   *
   * @var string
   */
  protected $countryCode;

  /**
   * The parent id.
   *
   * @var string
   */
  protected $parentId;

  /**
   * The parent entity.
   *
   * @var \CommerceGuys\Addressing\Model\SubdivisionInterface
   */
  protected $parent;

  /**
   * The subdivision id.
   *
   * @var string
   */
  protected $id;

  /**
   * The subdivision code.
   *
   * @var string
   */
  protected $code;

  /**
   * The subdivision name.
   *
   * @var string
   */
  protected $name;

  /**
   * The postal code pattern.
   *
   * @var string
   */
  protected $postalCodePattern;

  /**
   * The children entities.
   *
   * @var \CommerceGuys\Addressing\Model\SubdivisionInterface[]
   */
  protected $children;

  /**
   * {@inheritdoc}
   */
  public function getCountryCode() {
    return $this->countryCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setCountryCode($countryCode) {
    $this->countryCode = $countryCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getParent() {
    if (empty($this->parentId)) {
      return NULL;
    }
    if (empty($this->parent)) {
      $this->parent = self::load($this->parentId);
    }

    return $this->parent;
  }

  /**
   * {@inheritdoc}
   */
  public function setParent(SubdivisionInterface $parent = NULL) {
    $this->parent = $parent;
    $this->parentId = $parent ? $parent->getId() : NULL;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->id = $id;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCode() {
    return $this->code;
  }

  /**
   * {@inheritdoc}
   */
  public function setCode($code) {
    $this->code = $code;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->name = $name;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPostalCodePattern() {
    return $this->postalCodePattern;
  }

  /**
   * {@inheritdoc}
   */
  public function setPostalCodePattern($postalCodePattern) {
    $this->postalCodePattern = $postalCodePattern;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChildren() {
    if (!isset($this->children)) {
      $storage = $this->entityManager()->getStorage('subdivision');
      $this->children = $storage->loadByProperties(array('parentId' => $this->id));
    }

    return $this->children;
  }

  /**
   * {@inheritdoc}
   */
  public function setChildren($children) {
    $this->children = $children;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasChildren() {
    $children = $this->getChildren();
    return !empty($children);
  }

  /**
   * {@inheritdoc}
   */
  public function addChild(SubdivisionInterface $child) {
    if (!$this->hasChild($child)) {
      $this->children[] = $child;
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeChild(SubdivisionInterface $child) {
    if ($this->hasChild($child)) {
      // Remove the child and rekey the array.
      $index = array_search($child, $this->children, TRUE);
      unset($this->children[$index]);
      $this->children = array_values($this->children);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasChild(SubdivisionInterface $child) {
    $children = $this->getChildren();
    return in_array($child, $children, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $parameters = [];
    if ($rel == 'collection') {
      $parameters['address_format'] = $this->countryCode;
      $parameters['parent'] = $this->parentId;
    }
    else {
      $parameters['subdivision'] = $this->id;
    }

    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    // If this entity is not new, load the original entity for comparison.
    if (!$this->isNew()) {
      $original = $storage->loadUnchanged($this->getOriginalId());
      // Ensure that the UUID cannot be changed for an existing entity.
      if ($original && ($original->uuid() != $this->uuid())) {
        throw new ConfigDuplicateUUIDException(String::format('Attempt to save a configuration entity %id with UUID %uuid when this entity already exists with UUID %original_uuid', array('%id' => $this->id(), '%uuid' => $this->uuid(), '%original_uuid' => $original->uuid())));
      }
    }
  }

}
