<?php

/**
 * @file
 * Contains \Drupal\address\Plugin\Field\FieldWidget\AddressDefaultWidget.
 */

namespace Drupal\address\Plugin\Field\FieldWidget;

use CommerceGuys\Addressing\Enum\AddressField;
use CommerceGuys\Addressing\Repository\AddressFormatRepositoryInterface;
use CommerceGuys\Addressing\Repository\CountryRepositoryInterface;
use CommerceGuys\Addressing\Repository\SubdivisionRepositoryInterface;
use Drupal\address\Entity\AddressFormatInterface;
use Drupal\address\Event\AddressEvents;
use Drupal\address\Event\InitialValuesEvent;
use Drupal\address\FieldHelper;
use Drupal\address\LabelHelper;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'address' widget.
 *
 * @FieldWidget(
 *   id = "address_default",
 *   label = @Translation("Address"),
 *   field_types = {
 *     "address"
 *   },
 * )
 */
class AddressDefaultWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The address format repository.
   *
   * @var \CommerceGuys\Addressing\Repository\AddressFormatRepositoryInterface
   */
  protected $addressFormatRepository;

  /**
   * The country repository.
   *
   * @var \CommerceGuys\Addressing\Repository\CountryRepositoryInterface
   */
  protected $countryRepository;

  /**
   * The subdivision repository.
   *
   * @var \CommerceGuys\Addressing\Repository\SubdivisionRepositoryInterface
   */
  protected $subdivisionRepository;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The size attributes for fields likely to be inlined.
   *
   * @var array
   */
  protected $sizeAttributes = [
    AddressField::ADMINISTRATIVE_AREA => 30,
    AddressField::LOCALITY => 30,
    AddressField::DEPENDENT_LOCALITY => 30,
    AddressField::POSTAL_CODE => 10,
    AddressField::SORTING_CODE => 10,
  ];

  /**
   * Constructs a AddressDefaultWidget object.
   *
   * @param string $pluginId
   *   The plugin_id for the widget.
   * @param mixed $pluginDefinition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $thirdPartySettings
   *   Any third party settings.
   * @param \CommerceGuys\Addressing\Repository\AddressFormatRepositoryInterface $addressFormatRepository
   *   The address format repository.
   * @param \CommerceGuys\Addressing\Repository\CountryRepositoryInterface $countryRepository
   *   The country repository.
   * @param \CommerceGuys\Addressing\Repository\SubdivisionRepositoryInterface $subdivisionRepository
   *   The subdivision repository.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct($pluginId, $pluginDefinition, FieldDefinitionInterface $fieldDefinition, array $settings, array $thirdPartySettings, AddressFormatRepositoryInterface $addressFormatRepository, CountryRepositoryInterface $countryRepository, SubdivisionRepositoryInterface $subdivisionRepository, EventDispatcherInterface $eventDispatcher, ConfigFactoryInterface $configFactory) {
    parent::__construct($pluginId, $pluginDefinition, $fieldDefinition, $settings, $thirdPartySettings);

    $this->addressFormatRepository = $addressFormatRepository;
    $this->countryRepository = $countryRepository;
    $this->subdivisionRepository = $subdivisionRepository;
    $this->eventDispatcher = $eventDispatcher;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    // @see \Drupal\Core\Field\WidgetPluginManager::createInstance().
    return new static(
      $pluginId,
      $pluginDefinition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('address.address_format_repository'),
      $container->get('address.country_repository'),
      $container->get('address.subdivision_repository'),
      $container->get('event_dispatcher'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'default_country' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $formState) {
    $countryList = $this->countryRepository->getList();

    $element = [];
    $element['default_country'] = [
      '#type' => 'select',
      '#title' => $this->t('Default country'),
      '#options' => ['site_default' => $this->t('- Site default -')] + $countryList,
      '#default_value' => $this->getSetting('default_country'),
      '#empty_value' => '',
    ];

    return $element;
  }

  /**
   * Gets the initial values for the widget.
   *
   * This is a replacement for the disabled default values functionality.
   *
   * @see address_form_field_config_edit_form_alter()
   *
   * @param array $countryList
   *   The filtered country list, in the country_code => name format.
   *
   * @return array
   *   The initial values, keyed by property.
   */
  protected function getInitialValues(array $countryList) {
    $defaultCountry = $this->getSetting('default_country');
    // Resolve the special site_default option.
    if ($defaultCountry == 'site_default') {
      $defaultCountry = $this->configFactory->get('system.date')->get('country.default');
    }
    // Fallback to the first country in the list if the default country is not
    // available, or is empty even though the field is required.
    $notAvailable = $defaultCountry && !isset($countryList[$defaultCountry]);
    $emptyButRequired = empty($defaultCountry) && $this->fieldDefinition->isRequired();
    if ($notAvailable || $emptyButRequired) {
      $defaultCountry = key($countryList);
    }

    $initialValues = [
      'country_code' => $defaultCountry,
      'administrative_area' => '',
      'locality' => '',
      'dependent_locality' => '',
      'postal_code' => '',
      'sorting_code' => '',
      'address_line1' => '',
      'address_line2' => '',
      'organization' => '',
      'recipient' => '',
    ];
    // Allow other modules to alter the values.
    $event = new InitialValuesEvent($initialValues, $this->fieldDefinition);
    $this->eventDispatcher->dispatch(AddressEvents::INITIAL_VALUES, $event);
    $initialValues = $event->getInitialValues();

    return $initialValues;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $formState) {
    $fieldName = $this->fieldDefinition->getName();
    $idPrefix = implode('-', array_merge($element['#field_parents'], [$fieldName]));
    $wrapperId = Html::getUniqueId($idPrefix . '-ajax-wrapper');
    $item = $items[$delta];
    $fullCountryList = $this->countryRepository->getList();
    $countryList = $fullCountryList;
    $availableCountries = $item->getAvailableCountries();
    if (!empty($availableCountries)) {
      $countryList = array_intersect_key($countryList, $availableCountries);
    }
    // If the form has been rebuilt via AJAX, use the values from user input.
    // $formState->getValues() can't be used here because it's empty due to
    // #limit_validaiton_errors.
    $parents = array_merge($element['#field_parents'], [$fieldName, $delta]);
    $values = NestedArray::getValue($formState->getUserInput(), $parents, $hasInput);
    if (!$hasInput) {
      $values = $item->isEmpty() ? $this->getInitialValues($countryList) : $item->toArray();
    }

    $countryCode = $values['country_code'];
    if (!empty($countryCode) && !isset($countryList[$countryCode])) {
      // This item's country is no longer available. Add it back to the top
      // of the list to ensure all data is displayed properly. The validator
      // can then prevent the save and tell the user to change the country.
      $missingElement = [
        $countryCode => $fullCountryList[$countryCode],
      ];
      $countryList = $missingElement + $countryList;
    }

    // Calling initializeLangcode() every time, and not just when the field
    // is empty, ensures that the langcode can be changed on subsequent
    // edits (because the entity or interface language changed, for example).
    $langcode = $item->initializeLangcode();

    $element += [
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#open' => TRUE,
      '#prefix' => '<div id="' . $wrapperId . '">',
      '#suffix' => '</div>',
      '#pre_render' => [
        ['Drupal\Core\Render\Element\Details', 'preRenderDetails'],
        ['Drupal\Core\Render\Element\Details', 'preRenderGroup'],
        [get_class($this), 'groupElements'],
      ],
      '#after_build' => [
        [get_class($this), 'clearValues'],
      ],
      '#attached' => [
        'library' => ['address/form'],
      ],
      // Pass the id along to other methods.
      '#wrapper_id' => $wrapperId,
    ];
    $element['langcode'] = [
      '#type' => 'hidden',
      '#value' => $langcode,
    ];
    // Hide the country dropdown when there is only one possible value.
    if (count($countryList) == 1 && $this->fieldDefinition->isRequired()) {
      $countryCode = key($availableCountries);
      $element['country_code'] = [
        '#type' => 'hidden',
        '#value' => $countryCode,
      ];
    }
    else {
      $element['country_code'] = [
        '#type' => 'select',
        '#title' => $this->t('Country'),
        '#options' => $countryList,
        '#default_value' => $countryCode,
        '#required' => $element['#required'],
        '#empty_value' => '',
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => [get_class($this), 'ajaxRefresh'],
          'wrapper' => $wrapperId,
        ],
        '#attributes' => [
          'class' => ['country'],
          'autocomplete' => 'country',
        ],
        '#weight' => -100,
      ];
    }
    if (!empty($countryCode)) {
      $element = $this->addressElements($element, $values);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $formState) {
    return NestedArray::getValue($element, $violation->arrayPropertyPath);
  }

  /**
   * Builds the format-specific address elements.
   *
   * @param array $element
   *   The existing form element array.
   * @param array $values
   *   An array of address values, keyed by property name.
   *
   * @return array
   *   The modified form element array containing the format specific elements.
   */
  protected function addressElements(array $element, array $values) {
    $addressFormat = $this->addressFormatRepository->get($values['country_code']);
    $requiredFields = $addressFormat->getRequiredFields();
    $labels = LabelHelper::getFieldLabels($addressFormat);
    foreach ($addressFormat->getGroupedFields() as $lineIndex => $lineFields) {
      if (count($lineFields) > 1) {
        // Used by the #pre_render callback to group fields inline.
        $element['container' . $lineIndex] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['address-container-inline'],
          ],
        ];
      }

      foreach ($lineFields as $fieldIndex => $field) {
        $property = FieldHelper::getPropertyName($field);
        $class = str_replace('_', '-', $property);

        $element[$property] = [
          '#type' => 'textfield',
          '#title' => $labels[$field],
          '#default_value' => isset($values[$property]) ? $values[$property] : '',
          '#required' => in_array($field, $requiredFields),
          '#size' => isset($this->sizeAttributes[$field]) ? $this->sizeAttributes[$field] : 60,
          '#attributes' => [
            'class' => [$class],
            'autocomplete' => FieldHelper::getAutocompleteAttribute($field),
          ],
        ];
        if (count($lineFields) > 1) {
          $element[$property]['#group'] = $lineIndex;
        }
      }
    }
    // Hide the label for the second address line.
    if (isset($element['address_line2'])) {
      $element['address_line2']['#title_display'] = 'invisible';
    }
    // Hide fields that have been disabled in the address field settings.
    $enabledFields = array_filter($this->getFieldSetting('fields'));
    $disabledFields = array_diff(AddressField::getAll(), $enabledFields);
    foreach ($disabledFields as $field) {
      $property = FieldHelper::getPropertyName($field);
      $element[$property]['#access'] = FALSE;
    }
    // Add predefined options to the created subdivision elements.
    $element = $this->processSubdivisionElements($element, $values, $addressFormat);

    return $element;
  }

  /**
   * Processes the subdivision elements, adding predefined values where found.
   *
   * @param array $element
   *   The existing form element array.
   * @param array $values
   *   An array of address values, keyed by property name.
   * @param \Drupal\address\Entity\AddressFormatInterface $addressFormat
   *   The address format.
   *
   * @return array
   *   The processed form element array.
   */
  protected function processSubdivisionElements(array $element, array $values, AddressFormatInterface $addressFormat) {
    $depth = $this->subdivisionRepository->getDepth($values['country_code']);
    if ($depth === 0) {
      // No predefined data found.
      return $element;
    }

    $subdivisionProperties = [];
    foreach ($addressFormat->getUsedSubdivisionFields() as $field) {
      $subdivisionProperties[] = FieldHelper::getPropertyName($field);
    }
    // Load and insert the subdivisions for each parent id.
    $currentDepth = 1;
    foreach ($subdivisionProperties as $index => $property) {
      if (!isset($element[$property]) || !Element::isVisibleElement($element[$property])) {
        break;
      }
      $parentProperty = $index ? $subdivisionProperties[$index - 1] : NULL;
      if ($parentProperty && empty($values[$parentProperty])) {
        break;
      }
      $parentId = $parentProperty ? $values[$parentProperty] : NULL;
      $subdivisions = $this->subdivisionRepository->getList($values['country_code'], $parentId);
      if (empty($subdivisions)) {
        break;
      }

      $element[$property]['#type'] = 'select';
      $element[$property]['#options'] = $subdivisions;
      $element[$property]['#empty_value'] = '';
      unset($element[$property]['#size']);
      if ($currentDepth < $depth) {
        $element[$property]['#ajax'] = [
          'callback' => [get_class($this), 'ajaxRefresh'],
          'wrapper' => $element['#wrapper_id'],
        ];
      }

      $currentDepth++;
    }

    return $element;
  }

  /**
   * Groups elements with the same #group so that they can be inlined.
   */
  public static function groupElements(array $element) {
    $sort = [];
    foreach (Element::getVisibleChildren($element) as $key) {
      if (isset($element[$key]['#group'])) {
        // Copy the element to the container and remove the original.
        $groupIndex = $element[$key]['#group'];
        $containerKey = 'container' . $groupIndex;
        $element[$containerKey][$key] = $element[$key];
        unset($element[$key]);
        // Mark the container for sorting.
        if (!in_array($containerKey, $sort)) {
          $sort[] = $containerKey;
        }
      }
    }
    // Sort the moved elements, so that their #weight stays respected.
    foreach ($sort as $key) {
      uasort($element[$key], ['Drupal\Component\Utility\SortArray', 'sortByWeightProperty']);
    }

    return $element;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $formState) {
    $countryElement = $formState->getTriggeringElement();
    $addressElement = NestedArray::getValue($form, array_slice($countryElement['#array_parents'], 0, -1));

    return $addressElement;
  }

  /**
   * Clears the country-specific form values when the country changes.
   *
   * Implemented as an #after_build callback because #after_build runs before
   * validation, allowing the values to be cleared early enough to prevent the
   * "Illegal choice" error.
   */
  public static function clearValues(array $element, FormStateInterface $formState) {
    $triggeringElement = $formState->getTriggeringElement();
    if (!$triggeringElement) {
      return $element;
    }

    $triggeringElementName = end($triggeringElement['#parents']);
    if ($triggeringElementName == 'country_code') {
      $keys = [
        'dependent_locality', 'locality', 'administrative_area',
        'postal_code', 'sorting_code',
      ];
      $input = &$formState->getUserInput();
      foreach ($keys as $key) {
        $parents = array_merge($element['#parents'], [$key]);
        NestedArray::setValue($input, $parents, '');
        $element[$key]['#value'] = '';
      }
    }

    return $element;
  }

}
