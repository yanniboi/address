<?php

/**
 * @file
 * Contains \Drupal\address\DataProvider.
 */

namespace Drupal\address;

use CommerceGuys\Addressing\Provider\DataProviderInterface;
use CommerceGuys\Addressing\Repository\SubdivisionRepositoryInterface;
use CommerceGuys\Intl\Country\CountryRepositoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Defines the data provider, a facade in front of different data sources.
 *
 * Serves as the single point of contact between the data layer and the
 * module/underlying library.
 */
class DataProvider implements DataProviderInterface {

  /**
   * The country repository.
   *
   * @var \CommerceGuys\Intl\Country\CountryRepositoryInterface
   */
  protected $countryRepository;

  /**
   * The address format storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $formatStorage;

  /**
   * The subdivision repository.
   *
   * @var \CommerceGuys\Addressing\Repository\SubdivisionRepositoryInterface
   */
  protected $subdivisionRepository;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Creates a DataProvider instance.
   *
   * @param \CommerceGuys\Intl\Country\CountryRepositoryInterface $countryRepository
   *   The country repository.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   * @param \CommerceGuys\Addressing\Repository\SubdivisionRepositoryInterface $subdivisionRepository
   *   The subdivision repository.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(CountryRepositoryInterface $countryRepository, EntityManagerInterface $entityManager, SubdivisionRepositoryInterface $subdivisionRepository, LanguageManagerInterface $languageManager) {
    $this->countryRepository = $countryRepository;
    $this->formatStorage = $entityManager->getStorage('address_format');
    $this->subdivisionRepository = $subdivisionRepository;
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountryName($countryCode, $locale = null) {
    $names = $this->getCountryNames($locale);
    return $names[$countryCode];
  }

  /**
   * {@inheritdoc}
   */
  public function getCountryNames($locale = null) {
    $locale = $this->processLocale($locale);
    return $this->countryRepository->getList($locale);
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressFormat($countryCode, $locale = null) {
    return $this->formatStorage->load($countryCode);
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressFormats($locale = null) {
    return $this->formatStorage->loadMultiple();
  }

  /**
   * {@inheritdoc}
   */
  public function getSubdivision($id, $locale = null) {
    $locale = $this->processLocale($locale);
    return $this->subdivisionRepository->get($id, $locale);
  }

  /**
   * {@inheritdoc}
   */
  public function getSubdivisions($countryCode, $parentId = null, $locale = null) {
    $locale = $this->processLocale($locale);
    return $this->subdivisionRepository->getAll($countryCode, $parentId, $locale);
  }

  /**
   * {@inheritdoc}
   */
  public function getSubdivisionList($countryCode, $parentId = null, $locale = null) {
    $locale = $this->processLocale($locale);
    return $this->subdivisionRepository->getList($countryCode, $parentId, $locale);
  }

  /**
   * Replaces an empty locale with the one currently active.
   *
   * @param string $locale
   *   The provided locale.
   *
   * @return string
   *   The processed locale.
   */
  protected function processLocale($locale) {
    if (is_null($locale)) {
      $locale = $this->languageManager->getConfigOverrideLanguage();
    }

    return $locale;
  }

}
