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
use Drupal\Core\Language\Language;

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
    return $this->countryRepository->getList($locale);
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressFormat($countryCode, $locale = null) {
    if ($locale) {
      $originalLanguage = $this->languageManager->getConfigOverrideLanguage();
      $this->languageManager->setConfigOverrideLanguage(new Language(array('id' => $locale)));
      $addressFormat = $this->formatStorage->load($countryCode);
      $this->languageManager->setConfigOverrideLanguage($originalLanguage);
    }
    else {
      $addressFormat = $this->formatStorage->load($countryCode);
    }

    return $addressFormat;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressFormats($locale = null) {
    if ($locale) {
      $originalLanguage = $this->languageManager->getConfigOverrideLanguage();
      $this->languageManager->setConfigOverrideLanguage(new Language(array('id' => $locale)));
      $addressFormats = $this->formatStorage->loadMultiple();
      $this->languageManager->setConfigOverrideLanguage($originalLanguage);
    }
    else {
      $addressFormats = $this->formatStorage->loadMultiple();
    }

    return $addressFormats;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubdivision($id, $locale = null) {
    return $this->subdivisionRepository->get($id, $locale);
  }

  /**
   * {@inheritdoc}
   */
  public function getSubdivisions($countryCode, $parentId = null, $locale = null) {
    return $this->subdivisionRepository->getAll($countryCode, $parentId, $locale);
  }

  /**
   * {@inheritdoc}
   */
  public function getSubdivisionList($countryCode, $parentId = null, $locale = null) {
    return $this->subdivisionRepository->getList($countryCode, $parentId, $locale);
  }

}
