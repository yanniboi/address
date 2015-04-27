<?php

/**
 * @file
 * Contains \Drupal\address\SubdivisionListBuilder.
 */

namespace Drupal\address;

use CommerceGuys\Addressing\Model\SubdivisionInterface;
use Drupal\address\AddressFormatInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of Subdivisions.
 */
class SubdivisionListBuilder extends ConfigEntityListBuilder {

  /**
   * The address format.
   *
   * @var \Drupal\address\AddressFormatInterface
   */
  protected $addressFormat;

  /**
   * The parent subdivision.
   *
   * @var \CommerceGuys\Addressing\Model\SubdivisionInterface
   */
  protected $parent;

  /**
   * Sets the address format.
   *
   * @param \Drupal\address\AddressFormatInterface $addressFormat
   *   The address format.
   */
  public function setAddressFormat(AddressFormatInterface $addressFormat) {
    $this->addressFormat = $addressFormat;
  }

  /**
   * Sets the parent subdivision.
   *
   * @param $parent
   *   The parent subdivision.
   */
  public function setParent(SubdivisionInterface $parent = NULL) {
    $this->parent = $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $values = array(
      'countryCode' => $this->addressFormat->getCountryCode(),
    );
    if ($this->parent) {
      $values['parentId'] = $this->parent->id();
    }

    return $this->storage->loadByProperties($values);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $operations = parent::getDefaultOperations($entity);
    $operations['children'] = array(
      'title' => $this->t('List children'),
      'weight' => 1000,
      'url' => Url::fromRoute('entity.subdivision.collection', array(
        'address_format' => $entity->getCountryCode(),
        'parent' => $entity->id(),
      )),
    );

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['code'] = $this->t('Code');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['name'] = $this->getLabel($entity);
    $row['code'] = $entity->getCode();

    return $row + parent::buildRow($entity);
  }

}
