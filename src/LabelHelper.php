<?php

/**
 * @file
 * Contains \Drupal\address\LabelHelper.
 */

namespace Drupal\address;

use CommerceGuys\Addressing\Enum\AddressField;
use CommerceGuys\Addressing\Enum\AdministrativeAreaType;
use CommerceGuys\Addressing\Enum\DependentLocalityType;
use CommerceGuys\Addressing\Enum\LocalityType;
use CommerceGuys\Addressing\Enum\PostalCodeType;
use Drupal\address\Entity\AddressFormatInterface;

/**
 * Provides translated labels for the library enums.
 */
class LabelHelper {

  /**
   * Gets the field labels suitable for the given address format.
   *
   * Intended to be shown to the end user, they sometimes use a more familiar
   * term than the field name (Company instead of Organization, Contact name
   * instead of Recipient, etc).
   *
   * @param \Drupal\address\Entity\AddressFormatInterface $addressFormat
   *   The address format.
   *
   * @return string[]
   *   An array of labels, keyed by field.
   */
  public static function getFieldLabels(AddressFormatInterface $addressFormat) {
    $administrativeAreaType = $addressFormat->getAdministrativeAreaType();
    $localityType = $addressFormat->getLocalityType();
    $dependentLocalityType = $addressFormat->getDependentLocalityType();
    $postalCodeType = $addressFormat->getPostalCodeType();

    return [
      AddressField::ADMINISTRATIVE_AREA => self::getAdministrativeAreaLabel($administrativeAreaType),
      AddressField::LOCALITY => self::getLocalityLabel($localityType),
      AddressField::DEPENDENT_LOCALITY => self::getDependentLocalityLabel($dependentLocalityType),
      AddressField::POSTAL_CODE => self::getPostalCodeLabel($postalCodeType),
      // Google's library always labels the sorting code field as "Cedex".
      AddressField::SORTING_CODE => t('Cedex'),
      AddressField::ADDRESS_LINE1 => t('Street address'),
      // The address line 2 label is usually shown only to screen-reader users.
      AddressField::ADDRESS_LINE2 => t('Street address line 2'),
      AddressField::ORGANIZATION => t('Company'),
      AddressField::RECIPIENT => t('Contact name'),
    ];
  }

  /**
   * Gets the generic field labels.
   *
   * Intended primarily for backend settings screens.
   *
   * @return string[]
   *   The field labels, keyed by field.
   */
  public static function getGenericFieldLabels() {
    return [
      AddressField::ADMINISTRATIVE_AREA => t('Administrative area'),
      AddressField::LOCALITY => t('Locality'),
      AddressField::DEPENDENT_LOCALITY => t('Dependent locality'),
      AddressField::POSTAL_CODE => t('Postal code'),
      AddressField::SORTING_CODE => t('Sorting code'),
      AddressField::ADDRESS_LINE1 => t('Address line 1'),
      AddressField::ADDRESS_LINE2 => t('Address line 2'),
      AddressField::ORGANIZATION => t('Organization'),
      AddressField::RECIPIENT => t('Recipient'),
    ];
  }

  /**
   * Gets the administrative area label for the given type.
   *
   * @param string $administrativeAreaType
   *   The administrative area type.
   *
   * @return string
   *   The administrative area label.
   */
  public static function getAdministrativeAreaLabel($administrativeAreaType) {
    if (!$administrativeAreaType) {
      return NULL;
    }
    AdministrativeAreaType::assertExists($administrativeAreaType);
    $labels = self::getAdministrativeAreaLabels();

    return $labels[$administrativeAreaType];
  }

  /**
   * Gets all administrative area labels.
   *
   * @return string[]
   *   The administrative area labels, keyed by type.
   */
  public static function getAdministrativeAreaLabels() {
    return [
      AdministrativeAreaType::AREA => t('Area'),
      AdministrativeAreaType::COUNTY => t('County'),
      AdministrativeAreaType::DEPARTMENT => t('Department'),
      AdministrativeAreaType::DISTRICT => t('District'),
      AdministrativeAreaType::DO_SI => t('Do si'),
      AdministrativeAreaType::EMIRATE => t('Emirate'),
      AdministrativeAreaType::ISLAND => t('Island'),
      AdministrativeAreaType::OBLAST => t('Oblast'),
      AdministrativeAreaType::PARISH => t('Parish'),
      AdministrativeAreaType::PREFECTURE => t('Prefecture'),
      AdministrativeAreaType::PROVINCE => t('Province'),
      AdministrativeAreaType::STATE => t('State'),
    ];
  }

  /**
   * Gets the locality label for the given type.
   *
   * @param string $localityType
   *   The locality type.
   *
   * @return string
   *   The locality label.
   */
  public static function getLocalityLabel($localityType) {
    if (!$localityType) {
      return NULL;
    }
    LocalityType::assertExists($localityType);
    $labels = self::getLocalityLabels();

    return $labels[$localityType];
  }

  /**
   * Gets all locality labels.
   *
   * @return string[]
   *   The locality labels, keyed by type.
   */
  public static function getLocalityLabels() {
    return [
      LocalityType::CITY => t('City'),
      LocalityType::DISTRICT => t('District'),
      LocalityType::POST_TOWN => t('Post town'),
    ];
  }

  /**
   * Gets the dependent locality label for the given type.
   *
   * @param string $dependentLocalityType
   *   The dependent locality type.
   *
   * @return string
   *   The dependent locality label.
   */
  public static function getDependentLocalityLabel($dependentLocalityType) {
    if (!$dependentLocalityType) {
      return NULL;
    }
    DependentLocalityType::assertExists($dependentLocalityType);
    $labels = self::getDependentLocalityLabels();

    return $labels[$dependentLocalityType];
  }

  /**
   * Gets all dependent locality labels.
   *
   * @return string[]
   *   The dependent locality labels, keyed by type.
   */
  public static function getDependentLocalityLabels() {
    return [
      DependentLocalityType::DISTRICT => t('District'),
      DependentLocalityType::NEIGHBORHOOD => t('Neighborhood'),
      DependentLocalityType::VILLAGE_TOWNSHIP => t('Village township'),
      DependentLocalityType::SUBURB => t('Suburb'),
    ];
  }

  /**
   * Gets the postal code label for the given type.
   *
   * @param string $postalCodeType
   *   The postal code type.
   *
   * @return string
   *   The postal code label.
   */
  public static function getPostalCodeLabel($postalCodeType) {
    if (!$postalCodeType) {
      return NULL;
    }
    PostalCodeType::assertExists($postalCodeType);
    $labels = self::getPostalCodeLabels();

    return $labels[$postalCodeType];
  }

  /**
   * Gets all postal code labels.
   *
   * @return string[]
   *   The postal code labels, keyed by type.
   */
  public static function getPostalCodeLabels() {
    return [
      PostalCodeType::POSTAL => t('Postal code'),
      PostalCodeType::ZIP => t('Zip code'),
      PostalCodeType::PIN => t('Pin code'),
    ];
  }

}
