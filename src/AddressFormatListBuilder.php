<?php

/**
 * @file
 * Contains \Drupal\address\AddressFormatListBuilder.
 */

namespace Drupal\address;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of address formats.
 */
class AddressFormatListBuilder extends ConfigEntityListBuilder {

  /**
   * The country manager.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entityType) {
    return new static(
      $entityType,
      $container->get('entity.manager')->getStorage($entityType->id()),
      $container->get('country_manager')
    );
  }

  /**
   * Constructs a new AddressFormatListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Locale\CountryManagerInterface $countryManager
   *   The country manager.
   */
  public function __construct(EntityTypeInterface $entityType, EntityStorageInterface $storage, CountryManagerInterface $countryManager) {
    $this->entityTypeId = $entityType->id();
    $this->storage = $storage;
    $this->entityType = $entityType;
    $this->countryManager = $countryManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $operations = parent::getDefaultOperations($entity);

    // Show the 'List subdivisions' operation if the parent format
    // uses at least the administrative area subdivision field.
    $format = $entity->getFormat();
    if (strpos($format, '%administrative_area') !== FALSE) {
      $operations['subdivisions'] = array(
        'title' => $this->t('List subdivisions'),
        'url' => Url::fromRoute('entity.subdivision.collection', array(
          'address_format' => $entity->id(),
        )),
      );
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['country'] = $this->t('Country');
    $header['status'] = $this->t('Status');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['country'] = $this->getCountryName($entity->id());
    $row['status'] = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');

    return $row + parent::buildRow($entity);
  }

  /**
   * Returns the name of the country with the provided code.
   *
   * @param string $countryCode
   *   The country code.
   *
   * @return string
   *   The country name.
   */
  protected function getCountryName($countryCode) {
    if ($countryCode == 'ZZ') {
      return $this->t('Generic');
    }
    else {
      $countries = $this->countryManager->getList();
      return $countries[$countryCode];
    }
  }

}
