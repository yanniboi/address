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

/**
 * Default implementation of the address format importer.
 */
class AddressFormatImporter implements AddressFormatImporterInterface {

  /**
   * The address format storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $storage;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The library's address format repository.
   *
   * @var \CommerceGuys\Addressing\Repository\AddressFormatRepositoryInterface
   */
  protected $externalRepository;

  /**
   * Constructs a AddressFormatImporter object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(EntityManagerInterface $entityManager, LanguageManagerInterface $languageManager) {
    $this->storage = $entityManager->getStorage('address_format');
    $this->languageManager = $languageManager;
    $this->externalRepository = new AddressFormatRepository();
  }

  /**
   * {@inheritdoc}
   */
  public function importAll() {
    $addressFormats = $this->externalRepository->getAll();
    $countryCodes = array_keys($addressFormats);
    // It's nicer API-wise to just pass the country codes.
    // The external repository maintains a static cache, so the repeated ->get()
    // calls have minimal performance impact.
    $this->importEntities($countryCodes);

    if ($this->languageManager->isMultilingual()) {
      $languages = $this->languageManager->getLanguages(LanguageInterface::STATE_CONFIGURABLE);
      $this->importTranslations(array_keys($languages));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function importEntities(array $countryCodes) {
    foreach ($countryCodes as $countryCode) {
      $addressFormat = $this->externalRepository->get($countryCode);
      $values = [
        'langcode' => 'en',
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
      ];
      $entity = $this->storage->create($values);
      $entity->trustData()->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function importTranslations(array $langcodes) {
    $availableTranslations = $this->getAvailableTranslations();
    $availableTranslations = array_intersect_key($availableTranslations, array_flip($langcodes));
    foreach ($availableTranslations as $langcode => $countryCodes) {
      $addressFormats = $this->storage->loadMultiple($countryCodes);
      foreach ($addressFormats as $countryCode => $addressFormat) {
        $externalTranslation = $this->externalRepository->get($countryCode, $langcode);
        $configName = $addressFormat->getConfigDependencyName();
        $configTranslation = $this->languageManager->getLanguageConfigOverride($langcode, $configName);
        $configTranslation->set('format', $externalTranslation->getFormat());
        $configTranslation->save();
      }
    }
  }

  /**
   * Gets the available library translations.
   *
   * @return array
   *   Array keyed by language code who's value is an array of country codes
   *   related to that language.
   */
  protected function getAvailableTranslations() {
    // Hardcoded for now, since the library has no method for getting this data.
    $translations = [
      'ja' => ['JP'],
      'ko' => ['KR'],
      'th' => ['TH'],
      'zh' => ['MO', 'CN'],
      'zh-hant' => ['HK', 'TW'],
    ];

    return $translations;
  }

}
