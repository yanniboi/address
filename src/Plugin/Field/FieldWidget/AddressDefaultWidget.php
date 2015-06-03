<?php

/**
 * @file
 * Contains \Drupal\address\Plugin\Field\FieldWidget\AddressDefaultWidget.
 */

namespace Drupal\address\Plugin\Field\FieldWidget;

use CommerceGuys\Addressing\Repository\AddressFormatRepositoryInterface;
use CommerceGuys\Addressing\Repository\CountryRepositoryInterface;
use CommerceGuys\Addressing\Repository\SubdivisionRepositoryInterface;
use Drupal\address\FieldHelper;
use Drupal\address\LabelHelper;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   */
  public function __construct($pluginId, $pluginDefinition, FieldDefinitionInterface $fieldDefinition, array $settings, array $thirdPartySettings, AddressFormatRepositoryInterface $addressFormatRepository, CountryRepositoryInterface $countryRepository, SubdivisionRepositoryInterface $subdivisionRepository) {
    parent::__construct($pluginId, $pluginDefinition, $fieldDefinition, $settings, $thirdPartySettings);

    $this->addressFormatRepository = $addressFormatRepository;
    $this->countryRepository = $countryRepository;
    $this->subdivisionRepository = $subdivisionRepository;
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
      $container->get('address.subdivision_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $formState) {
    $fieldName = $this->fieldDefinition->getName();
    $idPrefix = implode('-', array_merge($element['#field_parents'], array($fieldName)));
    $wrapperId = Html::getUniqueId($idPrefix . '-ajax-wrapper');
    // If the form has been rebuilt via AJAX, use the values from user input.
    // $formState->getValues() can't be used here because it's empty due to
    // #limit_validaiton_errors.
    $parents = array_merge($element['#field_parents'], [$fieldName, $delta]);
    $values = NestedArray::getValue($formState->getUserInput(), $parents, $hasInput);
    if (!$hasInput) {
      $values = $items[$delta]->toArray();
    }

    $element += [
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#open' => TRUE,
      '#prefix' => '<div id="' . $wrapperId . '">',
      '#suffix' => '</div>',
      // Pass the id along to other methods.
      '#wrapper_id' => $wrapperId,
    ];
    $element['country_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#options' => $this->countryRepository->getList(),
      '#default_value' => $values['country_code'],
      '#empty_value' => '',
      '#limit_validation_errors' => [],
      '#element_validate' => [
        [get_class($this), 'clearValues'],
      ],
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
    if (!empty($values['country_code'])) {
      $element = $this->addressElements($element, $values);
    }

    return $element;
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
    $groupedFields = $addressFormat->getGroupedFields();
    $labels = LabelHelper::getFieldLabels($addressFormat);
    foreach ($groupedFields as $lineFields) {
      foreach ($lineFields as $field) {
        $property = FieldHelper::getPropertyName($field);
        $class = str_replace('_', '-', $property);

        $element[$property] = [
          '#type' => 'textfield',
          '#title' => $labels[$field],
          '#default_value' => $values[$property],
          '#required' => in_array($field, $requiredFields),
          '#attributes' => [
            'class' => [$class],
            'autocomplete' => FieldHelper::getAutocompleteAttribute($field),
          ],
        ];
      }
    }
    // Hide the label for the second address line.
    if (isset($element['address_line2'])) {
      $element['address_line2']['#title_display'] = 'invisible';
    }
    // Add predefined options to the created subdivision elements.
    $element = $this->processSubdivisionElements($element, $values);

    return $element;
  }

  /**
   * Processes the subdivision elements, adding predefined values where found.
   *
   * @param array $element
   *   The existing form element array.
   * @param array $values
   *   An array of address values, keyed by property name.
   *
   * @return array
   *   The processed form element array.
   */
  protected function processSubdivisionElements(array $element, array $values) {
    if (!isset($element['administrative_area'])) {
      // The current format doesn't have any subdivision fields.
      return;
    }

    // Add a parent id to each found subdivision element.
    $element['administrative_area']['#parent_id'] = 0;
    if (isset($element['locality'])) {
      $element['locality']['#parent_id'] = $values['administrative_area'];
    }
    if (isset($element['dependent_locality'])) {
      $element['dependent_locality']['#parent_id'] = $values['locality'];
    }
    // Load and insert the subdivisions for each parent id.
    foreach (['administrative_area', 'locality', 'dependent_locality'] as $property) {
      $parentId = $element[$property]['#parent_id'];
      if ($parentId === '') {
        break;
      }
      $subdivisions = $this->subdivisionRepository->getList($values['country_code'], $parentId);
      if (empty($subdivisions)) {
        break;
      }

      $element[$property]['#type'] = 'select';
      $element[$property]['#options'] = $subdivisions;
      $element[$property]['#empty_value'] = '';
      if (in_array($property, ['administrative_area', 'locality'])) {
        // @todo Add a way to know whether the next level has any subdivisions.
        $element[$property]['#ajax'] = [
          'callback' => [get_class($this), 'ajaxRefresh'],
          'wrapper' => $element['#wrapper_id'],
        ];
      }
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
   * Clears the country-specific form state values when a country changes.
   */
  public static function clearValues(array $element, FormStateInterface $formState) {
    if ($element['#default_value'] != $element['#value']) {
      $elementParents = $element['#parents'];
      array_pop($elementParents);

      $keys = [
        'dependent_locality', 'locality', 'administrative_area',
        'postal_code', 'sorting_code',
      ];
      $input = &$formState->getUserInput();
      foreach ($keys as $key) {
        $parents = array_merge($elementParents, [$key]);
        NestedArray::setValue($input, $parents, '');
      }
    }
  }

}
