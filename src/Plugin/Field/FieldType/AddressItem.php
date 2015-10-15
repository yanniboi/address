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
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
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
        'langcode' => [
          'type' => 'varchar',
          'length' => 32,
        ],
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
    $properties = [];
    $properties['langcode'] = DataDefinition::create('language')
      ->setLabel(t('The language code.'));
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
      'langcode_override' => '',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $languages = \Drupal::languageManager()->getLanguages(LanguageInterface::STATE_ALL);
    $languageOptions = [];
    foreach ($languages as $langcode => $language) {
      // Only list real languages (English, French, but not "Not specified").
      if (!$language->isLocked()) {
        $languageOptions[$langcode] = $language->getName();
      }
    }

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
    $element['langcode_override'] = [
      '#type' => 'select',
      '#title' => $this->t('Language override'),
      '#description' => $this->t('Ensures entered addresses are always formatted in the same language.'),
      '#options' => $languageOptions,
      '#default_value' => $this->getSetting('langcode_override'),
      '#empty_option' => $this->t('- No override -'),
      '#access' => \Drupal::languageManager()->isMultilingual(),
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
   * Initializes and returns the langcode property for the current field.
   *
   * Some countries use separate address formats for the local language VS
   * other languages. For example, China uses major-to-minor ordering
   * when the address is entered in Chinese, and minor-to-major when the
   * address is entered in other languages.
   * This means that the address must remember which language it was
   * entered in, to ensure consistent formatting later on.
   *
   * - For translatable entities this information comes from the field langcode.
   * - Non-translatable entities have no way to provide this information, since
   *   the field langcode never changes. In this case the field must store
   *   the interface language at the time of address creation.
   * - It is also possible to override the used language via field settings,
   *   in case the language is always known (e.g. a field storing the "english
   *   address" on a chinese article).
   *
   * The langcode property is intepreted by getLocale(), and in case it's NULL,
   * the field langcode is returned instead (indicating a non-multilingual site
   * or a translatable parent entity).
   *
   * @return string|null
   *   The langcode, or NULL if the field langcode should be used instead.
   */
  public function initializeLangcode() {
    $this->langcode = NULL;
    $languageManager = \Drupal::languageManager();
    if (!$languageManager->isMultilingual()) {
      return;
    }

    if ($override = $this->getSetting('langcode_override')) {
      $this->langcode = $override;
    }
    elseif (!$this->getEntity()->isTranslatable()) {
      $this->langcode = $languageManager->getConfigOverrideLanguage()->getId();
    }

    return $this->langcode;
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
    $langcode = $this->langcode;
    if (!$langcode) {
      // If no langcode was stored, fallback to the field langcode.
      // Documented in initializeLangcode().
      $langcode = $this->getLangcode();
    }

    return $langcode;
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
  public function getAdministrativeArea() {
    return $this->administrative_area;
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
  public function getDependentLocality() {
    return $this->dependent_locality;
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
  public function getSortingCode() {
    return $this->sorting_code;
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
  public function getAddressLine2() {
    return $this->address_line2;
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
  public function getRecipient() {
    return $this->recipient;
  }

}
