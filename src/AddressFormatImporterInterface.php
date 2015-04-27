<?php

/**
 * @file
 * Contains \Drupal\address\AddressFormatImporterInterface.
 */

namespace Drupal\address;

/**
 * Defines an address format importer.
 *
 * Imports the address format data provided by the library into config entities.
 */
interface AddressFormatImporterInterface {

  /**
   * Starts a batch process that imports all available data.
   */
  public function startImport();

  /**
   * Imports address formats with the given country codes.
   *
   * @param array $countryCodes
   *   The country codes used to identify address formats.
   */
  public function importEntities(array $countryCodes);

  /**
   * Imports translations for the given language codes.
   *
   * @param array $langcodes
   *   Language codes used for the translations.
   */
  public function importTranslations(array $langcodes);

}
