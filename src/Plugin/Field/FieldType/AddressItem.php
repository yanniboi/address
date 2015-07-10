<?php

/**
 * @file
 * Contains \Drupal\address\Plugin\Field\FieldType\AddressItem.
 */

namespace Drupal\address\Plugin\Field\FieldType;

use CommerceGuys\Addressing\Enum\AddressField;
use Drupal\address\Event\AddressEvents;
use Drupal\address\Event\AvailableCountriesEvent;
use Drupal\address\AddressInterface;
use Drupal\address\LabelHelper;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
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
   * An altered list of available countries.
   *
   * @var array
   */
  protected static $availableCountries = [];

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
  public static function defaultFieldSettings() {
    return [
      'available_countries' => [],
      'fields' => array_values(AddressField::getAll()),
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $element['available_countries'] = [
      '#type' => 'select',
      '#title' => $this->t('Available countries'),
      '#description' => $this->t('If no countries are selected, all countries will be available.'),
      '#options' => \Drupal::service('address.country_repository')->getList(),
      '#default_value' => $this->getSetting('available_countries'),
      '#multiple' => TRUE,
      '#size' => 10,
    ];
    $element['fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Used fields'),
      '#description' => $this->t('Note: an address used for postal purposes needs all of the above fields.'),
      '#default_value' => $this->getSetting('fields'),
      '#options' => LabelHelper::getGenericFieldLabels(),
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * Gets the available countries for the current field.
   *
   * @return array
   *   A list of country codes.
   */
  public function getAvailableCountries() {
    // Alter the list once per field, instead of once per field delta.
    $fieldDefinition = $this->getFieldDefinition();
    $definitionId = spl_object_hash($fieldDefinition);
    if (!isset(static::$availableCountries[$definitionId])) {
      $availableCountries = array_filter($this->getSetting('available_countries'));
      $eventDispatcher = \Drupal::service('event_dispatcher');
      $event = new AvailableCountriesEvent($availableCountries, $fieldDefinition);
      $eventDispatcher->dispatch(AddressEvents::AVAILABLE_COUNTRIES, $event);
      static::$availableCountries[$definitionId] = $event->getAvailableCountries();
    }

    return static::$availableCountries[$definitionId];
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();
    $manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $availableCountries = $this->getAvailableCountries();
    $enabledFields = array_filter($this->getSetting('fields'));
    $constraints[] = $manager->create('Country', ['availableCountries' => $availableCountries]);
    $constraints[] = $manager->create('AddressFormat', ['fields' => $enabledFields]);

    return $constraints;
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
