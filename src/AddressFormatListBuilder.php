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
   * Drupal has 258 different languages, along with the generic language we
   * should be able to display all languages with a limit of 260. We could
   * calculate this, but that would be a bit of a waste since this probably wont
   * change.
   *
   * @var int
   */
  protected $limit = 260;

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
