<?php

/**
 * @file
 * Contains \Drupal\address\Plugin\ZoneMember\ZoneMemberCountry.
 */

namespace Drupal\address\Plugin\ZoneMember;

use CommerceGuys\Addressing\Enum\AddressField;
use CommerceGuys\Addressing\Model\AddressInterface;
use CommerceGuys\Addressing\Repository\AddressFormatRepositoryInterface;
use CommerceGuys\Addressing\Repository\CountryRepositoryInterface;
use CommerceGuys\Addressing\Repository\SubdivisionRepositoryInterface;
use CommerceGuys\Zone\PostalCodeHelper;
use Drupal\address\Entity\AddressFormatInterface;
use Drupal\address\FieldHelper;
use Drupal\address\LabelHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Matches a country, its subdivisions, and its postal codes.
 *
 * @ZoneMember(
 *   id = "country",
 *   name = @Translation("Country"),
 * )
 */
class ZoneMemberCountry extends ZoneMemberBase implements ContainerFactoryPluginInterface {

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
   * Constructs a new ZoneMemberCountry object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $pluginId
   *   The pluginId for the plugin instance.
   * @param mixed $pluginDefinition
   *   The plugin implementation definition.
   * @param \CommerceGuys\Addressing\Repository\AddressFormatRepositoryInterface $addressFormatRepository
   *   The address format repository.
   * @param \CommerceGuys\Addressing\Repository\CountryRepositoryInterface $countryRepository
   *   The country repository.
   * @param \CommerceGuys\Addressing\Repository\SubdivisionRepositoryInterface $subdivisionRepository
   *   The subdivision repository.
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, AddressFormatRepositoryInterface $addressFormatRepository, CountryRepositoryInterface $countryRepository, SubdivisionRepositoryInterface $subdivisionRepository) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);

    $this->addressFormatRepository = $addressFormatRepository;
    $this->countryRepository = $countryRepository;
    $this->subdivisionRepository = $subdivisionRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('address.address_format_repository'),
      $container->get('address.country_repository'),
      $container->get('address.subdivision_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'country_code' => '',
      'administrative_area' => '',
      'locality' => '',
      'dependent_locality' => '',
      'included_postal_codes' => '',
      'excluded_postal_codes' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $formState) {
    $form = parent::buildConfigurationForm($form, $formState);
    $values = $formState->getUserInput();
    if ($values) {
      $values += $this->defaultConfiguration();
    }
    else {
      $values = $this->configuration;
    }

    $wrapperId = Html::getUniqueId('zone-members-ajax-wrapper');
    $form += [
      '#prefix' => '<div id="' . $wrapperId . '">',
      '#suffix' => '</div>',
      '#after_build' => [
        [get_class($this), 'clearValues'],
      ],
      // Pass the id along to other methods.
      '#wrapper_id' => $wrapperId,
    ];
    $form['country_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#options' => $this->countryRepository->getList(),
      '#default_value' => $values['country_code'],
      '#limit_validation_errors' => [],
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [get_class($this), 'ajaxRefresh'],
        'wrapper' => $wrapperId,
      ],
    ];
    if (!empty($values['country_code'])) {
      $addressFormat = $this->addressFormatRepository->get($values['country_code']);
      $form = $this->buildSubdivisionElements($form, $values, $addressFormat);
      $form = $this->buildPostalCodeElements($form, $values, $addressFormat);
    }

    return $form;
  }

  /**
   * Builds the subdivision form elements.
   *
   * @param array $form
   *   The form.
   * @param array $values
   *   The form values.
   * @param \Drupal\address\Entity\AddressFormatInterface $addressFormat
   *  The address format for the selected country.
   *
   * @return array
   *   The form with the added subdivision elements.
   */
  protected function buildSubdivisionElements(array $form, array $values, AddressFormatInterface $addressFormat) {
    $depth = $this->subdivisionRepository->getDepth($values['country_code']);
    if ($depth === 0) {
      // No predefined data found.
      return $form;
    }

    $labels = LabelHelper::getFieldLabels($addressFormat);
    $subdivisionFields = $addressFormat->getUsedSubdivisionFields();
    $currentDepth = 1;
    foreach ($subdivisionFields as $index => $field) {
      $property = FieldHelper::getPropertyName($field);
      $parentProperty = $index ? FieldHelper::getPropertyName($subdivisionFields[$index - 1]) : NULL;
      if ($parentProperty && empty($values[$parentProperty])) {
        // No parent value selected.
        break;
      }
      $parentId = $parentProperty ? $values[$parentProperty] : NULL;
      $subdivisions = $this->subdivisionRepository->getList($values['country_code'], $parentId);
      if (empty($subdivisions)) {
        break;
      }

      $form[$property] = [
        '#type' => 'select',
        '#title' => $labels[$field],
        '#options' => $subdivisions,
        '#default_value' => $values[$property],
        '#empty_option' => $this->t('- All -'),
      ];
      if ($currentDepth < $depth) {
        $form[$property]['#ajax'] = [
          'callback' => [get_class($this), 'ajaxRefresh'],
          'wrapper' => $form['#wrapper_id'],
        ];
      }

      $currentDepth++;
    }

    return $form;
  }

  /**
   * Builds the postal code form elements.
   *
   * @param array $form
   *   The form.
   * @param array $values
   *   The form values.
   * @param \Drupal\address\Entity\AddressFormatInterface $addressFormat
   *  The address format for the selected country.
   *
   * @return array
   *   The form with the added postal code elements.
   */
  protected function buildPostalCodeElements(array $form, array $values, AddressFormatInterface $addressFormat) {
    if (!in_array(AddressField::POSTAL_CODE, $addressFormat->getUsedFields())) {
      // The address format doesn't use a postal code field.
      return $form;
    }

    $form['included_postal_codes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Included postal codes'),
      '#description' => $this->t('A regular expression ("/(35|38)[0-9]{3}/") or comma-separated list, including ranges ("98, 100:200")'),
      '#default_value' => $values['included_postal_codes'],
    ];
    $form['excluded_postal_codes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Excluded postal codes'),
      '#description' => $this->t('A regular expression ("/(35|38)[0-9]{3}/") or comma-separated list, including ranges ("98, 100:200")'),
      '#default_value' => $values['excluded_postal_codes'],
    ];

    return $form;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $formState) {
    $parents = $formState->getTriggeringElement()['#parents'];
    array_pop($parents);
    return NestedArray::getValue($form, $parents);
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
      $keys = ['dependent_locality', 'locality', 'administrative_area'];
      $input = &$formState->getUserInput();
      foreach ($keys as $key) {
        $parents = array_merge($element['#parents'], [$key]);
        NestedArray::setValue($input, $parents, '');
        $element[$key]['#value'] = '';
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $formState) {
    parent::submitConfigurationForm($form, $formState);

    if (!$formState->getErrors()) {
      $this->configuration['country_code'] = $formState->getValue('country_code');
      $this->configuration['administrative_area'] = $formState->getValue('administrative_area');
      $this->configuration['locality'] = $formState->getValue('locality');
      $this->configuration['dependent_locality'] = $formState->getValue('dependent_locality');
      $this->configuration['included_postal_codes'] = $formState->getValue('included_postal_codes');
      $this->configuration['excluded_postal_codes'] = $formState->getValue('excluded_postal_codes');
    }
  }

  /**
  * {@inheritdoc}
  */
  public function match(AddressInterface $address) {
    if ($address->getCountryCode() != $this->configuration['country_code']) {
      return FALSE;
    }

    $administrativeArea = $this->configuration['administrative_area'];
    $locality = $this->configuration['locality'];
    $dependentLocality = $this->configuration['dependent_locality'];
    if ($administrativeArea && $administrativeArea != $address->getAdministrativeArea()) {
      return FALSE;
    }
    if ($locality && $locality != $address->getLocality()) {
      return FALSE;
    }
    if ($dependentLocality && $dependentLocality != $address->getDependentLocality()) {
      return FALSE;
    }

    $includedPostalCodes = $this->configuration['included_postal_codes'];
    $excludedPostalCodes = $this->configuration['excluded_postal_codes'];
    if (!PostalCodeHelper::match($address->getPostalCode(), $includedPostalCodes, $excludedPostalCodes)) {
      return FALSE;
    }

    return TRUE;
  }

}
