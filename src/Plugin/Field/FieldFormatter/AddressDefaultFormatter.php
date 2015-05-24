<?php

/**
 * @file
 * Contains \Drupal\address\Plugin\field\formatter\AddressDefaultFormatter.
 */

namespace Drupal\address\Plugin\Field\FieldFormatter;

use CommerceGuys\Addressing\Enum\AddressField;
use CommerceGuys\Addressing\Model\AddressInterface;
use CommerceGuys\Addressing\Repository\AddressFormatRepositoryInterface;
use CommerceGuys\Addressing\Repository\CountryRepositoryInterface;
use CommerceGuys\Addressing\Repository\SubdivisionRepositoryInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'address_default' formatter.
 *
 * @FieldFormatter(
 *   id = "address_default",
 *   label = @Translation("Address"),
 *   field_types = {
 *     "address",
 *   },
 * )
 */
class AddressDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

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
   * Maps AddressField values to their matching properties.
   *
   * @var array
   */
  protected $propertyMapping = [
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

  /**
   * Constructs an AddressDefaultFormatter object.
   *
   * @param string $pluginId
   *   The plugin_id for the formatter.
   * @param mixed $pluginDefinition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $viewMode
   *   The view mode.
   * @param array $thirdPartySettings
   *   Any third party settings.
   * @param \CommerceGuys\Addressing\Repository\AddressFormatRepositoryInterface $addressFormatRepository
   *   The address format repository.
   * @param \CommerceGuys\Addressing\Repository\CountryRepositoryInterface $countryRepository
   *   The country repository.
   * @param \CommerceGuys\Addressing\Repository\SubdivisionRepositoryInterface $subdivisionRepository
   *   The subdivision repository.
   */
  public function __construct($pluginId, $pluginDefinition, FieldDefinitionInterface $fieldDefinition, array $settings, $label, $viewMode, array $thirdPartySettings, AddressFormatRepositoryInterface $addressFormatRepository, CountryRepositoryInterface $countryRepository, SubdivisionRepositoryInterface $subdivisionRepository) {
    parent::__construct($pluginId, $pluginDefinition, $fieldDefinition, $settings, $label, $viewMode, $thirdPartySettings);

    $this->addressFormatRepository = $addressFormatRepository;
    $this->countryRepository = $countryRepository;
    $this->subdivisionRepository = $subdivisionRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    // @see \Drupal\Core\Field\FormatterPluginManager::createInstance().
    return new static(
      $pluginId,
      $pluginDefinition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('address.address_format_repository'),
      $container->get('address.country_repository'),
      $container->get('address.subdivision_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#prefix' => '<p translate="no">',
        '#sufix' => '</p>',
        '#post_render' => [
          [get_class($this), 'postRender'],
        ],
      ];
      $elements[$delta] += $this->viewElement($item);
    }

    return $elements;
  }

  /**
   * Builds a renderable array for a single address item.
   *
   * @param \CommerceGuys\Addressing\Model\AddressInterface $address
   *   The address.
   *
   * @return array
   *   A renderable array.
   */
  protected function viewElement(AddressInterface $address) {
    $values = $this->getValues($address);
    $countryCode = $address->getCountryCode();
    $addressFormat = $this->addressFormatRepository->get($countryCode, $address->getLocale());
    $countries = $this->countryRepository->getList();

    $element = [];
    $element['address_format'] = [
      '#type' => 'value',
      '#value' => $addressFormat,
    ];
    $element['country_code'] = [
      '#type' => 'value',
      '#value' => $countryCode,
    ];
    $element['country'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => ['class' => ['country']],
      '#value' => $countries[$countryCode],
      '#placeholder' => '%country',
    ];
    foreach ($addressFormat->getUsedFields() as $field) {
      $property = $this->propertyMapping[$field];
      $class = str_replace('_', '-', $property);

      $element[$property] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => ['class' => [$class]],
        '#value' => $values[$field],
        '#placeholder' => '%' . $field,
      ];
    }

    return $element;
  }

  /**
   * Inserts the rendered elements into the format string.
   *
   * @param string $content
   *   The rendered element.
   * @param array $element
   *   An associative array containing the properties and children of the
   *   element.
   *
   * @return string
   *   The new rendered element.
   */
  public static function postRender($content, $element) {
    $addressFormat = $element['address_format']['#value'];
    $formatString = $addressFormat->getFormat();
    // Add the country to the bottom or the top of the format string,
    // depending on whether the format is minor-to-major or major-to-minor.
    if (strpos($formatString, AddressField::ADDRESS_LINE1) < strpos($formatString, AddressField::ADDRESS_LINE2)) {
      $formatString .= "\n" . '%country';
    }
    else {
      $formatString = '%country' . "\n" . $formatString;
    }

    $replacements = [];
    foreach (Element::getVisibleChildren($element) as $key) {
      $child = $element[$key];
      if (isset($child['#placeholder'])) {
        $replacements[$child['#placeholder']] = $child['#value'] ? $child['#markup'] : '';
      }
    }
    $content = self::replacePlaceholders($formatString, $replacements);
    $content = nl2br($content, FALSE);

    return $content;
  }

  /**
   * Replaces placeholders in the given string.
   *
   * @param string $string
   *   The string containing the placeholders.
   * @param array $replacements
   *   An array of replacements keyed by their placeholders.
   *
   * @return string
   *   The processed string.
   */
  public static function replacePlaceholders($string, array $replacements) {
    // Make sure the replacements don't have any unneeded newlines.
    $replacements = array_map('trim', $replacements);
    $string = strtr($string, $replacements);
    // Remove noise caused by empty placeholders.
    $lines = explode("\n", $string);
    foreach ($lines as $index => $line) {
      // Remove leading punctuation, excess whitespace.
      $line = trim(preg_replace('/^[-,]+/', '', $line, 1));
      $line = preg_replace('/\s\s+/', ' ', $line);
      $lines[$index] = $line;
    }
    // Remove empty lines.
    $lines = array_filter($lines);

    return implode("\n", $lines);
  }

  /**
   * Gets the address values used for rendering.
   *
   * @param \CommerceGuys\Addressing\Model\AddressInterface $address
   *   The address.
   *
   * @return array
   *   The values, keyed by address field.
   */
  protected function getValues(AddressInterface $address) {
    $values = [];
    foreach (AddressField::getAll() as $field) {
      $getter = 'get' . ucfirst($field);
      $values[$field] = $address->$getter();
    }

    // Replace the subdivision values with the names of any predefined ones.
    $subdivisionFields = [
      AddressField::ADMINISTRATIVE_AREA,
      AddressField::LOCALITY,
      AddressField::DEPENDENT_LOCALITY,
    ];
    foreach ($subdivisionFields as $field) {
      if (empty($values[$field])) {
        // This level is empty, so there can be no sublevels.
        break;
      }
      $subdivision = $this->subdivisionRepository->get($values[$field], $address->getLocale());
      if (!$subdivision) {
        // This level has no predefined subdivisions, stop.
        break;
      }

      $values[$field] = $subdivision->getCode();
      if (!$subdivision->hasChildren()) {
        // The current subdivision has no children, stop.
        break;
      }
    }

    return $values;
  }

}
