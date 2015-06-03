<?php

/**
 * @file
 * Contains \Drupal\address\FieldHelper.
 */

namespace Drupal\address;

use CommerceGuys\Addressing\Enum\AddressField;

/**
 * Provides property names and autocomplete attributes for AddressField values.
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

  /**
   * Gets the autocomplete attribute for the given AddressField value.
   *
   * Source: https://html.spec.whatwg.org/multipage/forms.html#autofill
   *
   * @param string $field
   *   An AddressField value.
   *
   * @return string
   *   The autocomplete attribute.
   */
  public static function getAutocompleteAttribute($field) {
    $autocompleteMapping = [
      'administrativeArea' => 'address-level1',
      'locality' => 'address-level2',
      'dependentLocality' => 'address-level3',
      'postalCode' => 'postal_code',
      'sortingCode' => 'sorting_code',
      'addressLine1' => 'address_line1',
      'addressLine2' => 'address_line2',
      'organization' => 'organization',
      'recipient' => 'name',
    ];

    return isset($autocompleteMapping[$field]) ? $autocompleteMapping[$field] : NULL;
  }

}
