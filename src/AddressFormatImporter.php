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
  public function startImport() {
    $operations = [];
    foreach (array_chunk($this->externalRepository->getAll(), 25, TRUE) as $addressFormats) {
      $operations[] = [
        [get_class($this), 'doImportEntities'],
        [array_keys($addressFormats)],
      ];
    }
    if ($this->languageManager->isMultilingual()) {
      $languages = $this->languageManager->getLanguages(LanguageInterface::STATE_CONFIGURABLE);
      $operations[] = [
        [get_class($this), 'doImportTranslations'],
        [array_keys($languages)],
      ];
    }

    batch_set([
      'title' => t('Importing address formats'),
      'init_message' => t('Preparing to import...'),
      'operations' => $operations,
    ]);
    // Drush requires the batch to be started manually.
    if (PHP_SAPI === 'cli' && function_exists("drush_backend_batch_process")) {
      drush_backend_batch_process();
    }
    // Or if the code is running with the CLI (such as simpletest), run
    // batch_process with progressive false to ensure address formats are
    // imported correctly
    elseif (PHP_SAPI === 'cli') {
      $batch =& batch_get();
      $batch['progressive'] = FALSE;
      batch_process();
    }
  }

  /**
   * Batch callback for importing address format entities.
   *
   * @param array $countryCodes
   *   The country codes used to identify address formats.
   * @param object &$context
   *   The context of the batch.
   */
  public static function doImportEntities(array $countryCodes, &$context) {
    $importer = \Drupal::service('address.address_format_importer');
    $importer->importEntities($countryCodes);

    $context['finished'] = 1;
  }

  /**
   * Batch callback for importing translations.
   *
   * @param array $langcodes
   *   Language codes used for the translations.
   * @param object &$context
   *   The context of the batch.
   */
  public static function doImportTranslations(array $langcodes, &$context) {
    $importer = \Drupal::service('address.address_format_importer');
    $importer->importTranslations($langcodes);

    $context['finished'] = 1;
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
   *   An array in the $languageCode => $countryCodes format.
   */
  protected function getAvailableTranslations()
  {
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
