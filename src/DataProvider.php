<?php

namespace Drupal\address;

use CommerceGuys\Addressing\Provider\DataProviderInterface;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Exposes Drupal data to the addressing library.
 *
 * Note that the methods ignore the $locale parameter because the loaded
 * data is already translated into the current language.
 */
class DataProvider implements DataProviderInterface
{

  /**
   * The country manager.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManager;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Creates a DataProvider instance.
   *
   * @param \Drupal\Core\Locale\CountryManagerInterface $countryManager
   *   The country manager.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   */
  public function __construct(CountryManagerInterface $countryManager, EntityManagerInterface $entityManager) {
    $this->countryManager = $countryManager;
    $this->entityManager = $entityManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountryName($countryCode, $locale = null) {
    $countries = $this->countryManager->getList();
    return $countries[$countryCode];
  }

  /**
   * {@inheritdoc}
   */
  public function getCountryNames($locale = null) {
    return $this->countryManager->getList();
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressFormat($countryCode, $locale = null) {
    return $this->entityManager->getStorage('address_format')->load($countryCode);
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressFormats($locale = null) {
    return $this->entityManager->getStorage('address_format')->loadMultiple();
  }

  /**
   * {@inheritdoc}
   */
  public function getSubdivision($id, $locale = null) {
    return $this->entityManager->getStorage('subdivision')->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function getSubdivisions($countryCode, $parentId = null, $locale = null) {
    return $this->entityManager->getStorage('subdivision')->loadByProperties(array(
      'countryCode' => $countryCode,
      'parentId' => $parentId,
    ));
  }
}
