<?php

/**
 * @file
 * Contains \Drupal\address\FieldHelper.
 */

namespace Drupal\address;

use CommerceGuys\Addressing\Enum\AddressField;

/**
 * Provides property names for AddressField values.
 */
class FieldHelper {

  /**
   * Gets the property name matching the given AddressField value.
   *
   * @param string $field
   *   An AddressField value.
   *
   * @return string
   *   The property name.
   */
  public static function getPropertyName($field) {
    $propertyMapping = [
      'administrativeArea' => 'administrative_area',
      'locality' => 'locality',
      'dependentLocality' => 'dependent_locality',
      'postalCode' => 'postal_code',
      'sortingCode' => 'sorting_code',
      'addressLine1' => 'address_line1',
      'addressLine2' => 'address_line2',
      'organization' => 'organization',
      'recipient' => 'recipient',
    ];

    return isset($propertyMapping[$field]) ? $propertyMapping[$field] : NULL;
  }

}
