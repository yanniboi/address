<?php

/**
 * @file
 * Contains \Drupal\address\AddressFormatImporter.
 */

namespace Drupal\address;

use CommerceGuys\Addressing\Repository\AddressFormatRepository;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\address\AddressFormatImporterInterface;
use Drupal\language\ConfigurableLanguageManagerInterface;


class AddressFormatImporter implements AddressFormatImporterInterface {

  /**
   * The address format manager.
   *
   * @var \CommerceGuys\Addressing\Repository\AddressFormatRepositoryInterface
   */
  protected $addressFormatRepository;

  /**
   * The address format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $addressFormatStorage;

  /**
   * The configurable language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new CurrencyImporter.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(EntityManagerInterface $entityManager, LanguageManagerInterface $languageManager) {
    $this->addressFormatStorage = $entityManager->getStorage('address_format');
    $this->languageManager = $languageManager;
    $this->addressFormatRepository = new AddressFormatRepository();
  }

  /**
   * {@inheritdoc}
   */
  public function getImportableAddressFormats() {
    $language = $this->languageManager->getCurrentLanguage();
    $importableAddressFormats = $this->addressFormatRepository->getAll($language->getId());
    $importedAddressFormats = $this->addressFormatStorage->loadMultiple();

    // Remove any already imported currencies.
    foreach ($importedAddressFormats as $addressFormat) {
      if (isset($importableAddressFormats[$addressFormat->id()])) {
        unset($importableAddressFormats[$addressFormat->id()]);
      }
    }

    return $importableAddressFormats;
  }

  /**
   * {@inheritdoc}
   */
  public function importAddressFormat($countryCode) {
    if ($this->addressFormatStorage->load($countryCode)) {
      return FALSE;
    }
    $language = $this->languageManager->getDefaultLanguage();
    $addressFormat = $this->getAddressFormat($countryCode, $language);

    $values = array(
      'countryCode' => $addressFormat->getCountryCode(),
      'format' => $addressFormat->getFormat(),
      'requiredFields' => $addressFormat->getRequiredFields(),
      'uppercaseFields' => $addressFormat->getUppercaseFields(),
      'administrativeAreaType' => $addressFormat->getAdministrativeAreaType(),
      'localityType' => $addressFormat->getLocalityType(),
      'dependentLocalityType' => $addressFormat->getDependentLocalityType(),
      'postalCodeType' => $addressFormat->getPostalCodeType(),
      'postalCodePattern' => $addressFormat->getPostalCodePattern(),
      'postalCodePrefix' => $addressFormat->getPostalCodePrefix(),
    );
    $entity = $this->addressFormatStorage->create($values);

    return $entity;
  }

  /**
   * Get a single currency.
   *
   * @param string $countryCode
   *   The country code.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language.
   *
   * @return CommerceGuys\Addressing\Model\AddressFormat
   *   Returns \CommerceGuys\Addressing\Model\AddressFormat
   */
  protected function getAddressFormat($countryCode, LanguageInterface $language) {
    return $this->addressFormatRepository->get($countryCode, $language->getId());
  }
}
