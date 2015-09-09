<?php

/**
 * @file
 * Contains \Drupal\address\Repository\AddressFormatRepository.
 */

namespace Drupal\address\Repository;

use CommerceGuys\Addressing\Repository\AddressFormatRepositoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Language\Language;

/**
 * Defines the address format repository.
 *
 * Address formats are stored as config entities.
 */
class AddressFormatRepository implements AddressFormatRepositoryInterface {

  /**
   * The address format storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $formatStorage;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Creates an AddressFormatRepository instance.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(EntityManagerInterface $entityManager, LanguageManagerInterface $languageManager) {
    $this->formatStorage = $entityManager->getStorage('address_format');
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public function get($countryCode, $locale = NULL) {
    if ($locale) {
      $originalLanguage = $this->languageManager->getConfigOverrideLanguage();
      $this->languageManager->setConfigOverrideLanguage(new Language(['id' => $locale]));
      $addressFormat = $this->formatStorage->load($countryCode);
      $this->languageManager->setConfigOverrideLanguage($originalLanguage);
    }
    else {
      $addressFormat = $this->formatStorage->load($countryCode);
    }

    if (!$addressFormat) {
      // No format found for the given country code, fallback to ZZ.
      $addressFormat = $this->formatStorage->load('ZZ');
    }

    return $addressFormat;
  }

  /**
   * {@inheritdoc}
   */
  public function getAll($locale = NULL) {
    if ($locale) {
      $originalLanguage = $this->languageManager->getConfigOverrideLanguage();
      $this->languageManager->setConfigOverrideLanguage(new Language(['id' => $locale]));
      $addressFormats = $this->formatStorage->loadMultiple();
      $this->languageManager->setConfigOverrideLanguage($originalLanguage);
    }
    else {
      $addressFormats = $this->formatStorage->loadMultiple();
    }

    return $addressFormats;
  }

}
