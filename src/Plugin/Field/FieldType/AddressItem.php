<?php

/**
 * @file
 * Contains \Drupal\address\Plugin\Field\FieldType\AddressItem.
 */

namespace Drupal\address\Plugin\Field\FieldType;

use CommerceGuys\Addressing\Model\AddressInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'address' field type.
 *
 * @FieldType(
 *   id = "address",
 *   label = @Translation("Address"),
 *   description = @Translation("An entity field containing a postal address"),
 *   default_widget = "address_default",
 *   default_formatter = "address_default"
 * )
 */
class AddressItem extends FieldItemBase implements AddressInterface {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $fieldDefinition) {
    return [
      'columns' => [
        'country_code' => [
          'type' => 'varchar',
          'length' => 2,
        ],
        'administrative_area' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'locality' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'dependent_locality' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'postal_code' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'sorting_code' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'address_line1' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'address_line2' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'organization' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'recipient' => [
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $fieldDefinition) {
    $properties['country_code'] = DataDefinition::create('string')
      ->setLabel(t('The two-letter country code.'));
    $properties['administrative_area'] = DataDefinition::create('string')
      ->setLabel(t('The top-level administrative subdivision of the country.'));
    $properties['locality'] = DataDefinition::create('string')
      ->setLabel(t('The locality (i.e. city).'));
    $properties['dependent_locality'] = DataDefinition::create('string')
      ->setLabel(t('The dependent locality (i.e. neighbourhood).'));
    $properties['postal_code'] = DataDefinition::create('string')
      ->setLabel(t('The postal code.'));
    $properties['sorting_code'] = DataDefinition::create('string')
      ->setLabel(t('The sorting code.'));
    $properties['address_line1'] = DataDefinition::create('string')
      ->setLabel(t('The first line of the address block.'));
    $properties['address_line2'] = DataDefinition::create('string')
      ->setLabel(t('The second line of the address block.'));
    $properties['organization'] = DataDefinition::create('string')
      ->setLabel(t('The organization'));
    $properties['recipient'] = DataDefinition::create('string')
      ->setLabel(t('The recipient.'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->country_code;
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function getLocale() {
    return $this->getLangcode();
  }

  /**
   * {@inheritdoc}
   */
  public function setLocale($locale) {
    // Our locale comes from the parent entity, so it can't be changed here.
    // Luckily, the setters aren't actually used by the commerceguys/addressing
    // validator and formatter.
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountryCode() {
    return $this->country_code;
  }

  /**
   * {@inheritdoc}
   */
  public function setCountryCode($countryCode) {
    $this->country_code = $countryCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdministrativeArea() {
    return $this->administrative_area;
  }

  /**
   * {@inheritdoc}
   */
  public function setAdministrativeArea($administrativeArea) {
    $this->administrative_area = $administrativeArea;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocality() {
    return $this->locality;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocality($locality) {
    $this->locality = $locality;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependentLocality() {
    return $this->dependent_locality;
  }

  /**
   * {@inheritdoc}
   */
  public function setDependentLocality($dependentLocality) {
    $this->dependent_locality = $dependentLocality;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPostalCode() {
    return $this->postal_code;
  }

  /**
   * {@inheritdoc}
   */
  public function setPostalCode($postalCode) {
    $this->postal_code = $postalCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSortingCode() {
    return $this->sorting_code;
  }

  /**
   * {@inheritdoc}
   */
  public function setSortingCode($sortingCode) {
    $this->sorting_code = $sortingCode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressLine1() {
    return $this->address_line1;
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressLine1($addressLine1) {
    $this->address_line1 = $addressLine1;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressLine2() {
    return $this->address_line2;
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressLine2($addressLine2) {
    $this->address_line2 = $addressLine2;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrganization() {
    return $this->organization;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrganization($organization) {
    $this->organization = $organization;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipient() {
    return $this->recipient;
  }

  /**
   * {@inheritdoc}
   */
  public function setRecipient($recipient) {
    $this->recipient = $recipient;
    return $this;
  }
}
