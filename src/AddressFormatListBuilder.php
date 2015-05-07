<?php

/**
 * @file
 * Contains \Drupal\address\AddressFormatListBuilder.
 */

namespace Drupal\address;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of address formats.
 */
class AddressFormatListBuilder extends ConfigEntityListBuilder {

  /**
   * The number of entities to list per page.
   *
   * @var int
   */
  protected $limit = 210;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['country'] = $this->t('Country');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['country'] = $entity->label();

    return $row + parent::buildRow($entity);
  }

}
