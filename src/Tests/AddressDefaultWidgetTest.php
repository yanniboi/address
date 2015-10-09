<?php

/**
 * @file
 * Contains \Drupal\address\Tests\AddressDefaultWidgetTest.
 */

namespace Drupal\address\Tests;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the default address widget.
 *
 * @group address
 */
class AddressDefaultWidgetTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'language',
    'user',
    'field',
    'field_ui',
    'node',
    'address',
  ];

  /**
   * User with permission to administer entites.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Address field instance.
   *
   * @var Drupal\Field\FieldConfigInterface
   */
  protected $fieldInstance;

  /**
   * Entity form display.
   *
   * @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   */
  protected $formDisplay;

  /*
   * URL to add new content.
   *
   * @var string
   */
  protected $formContentAddUrl;

  /*
   * URL to field's configuration form.
   *
   * @var string
   */
  protected $formFieldConfigUrl;

  /**
   * The country repository.
   *
   * @var CountryRepositoryInterface
   */
  protected $countryRepository;

  /**
   * The subdivision repository.
   *
   * @var SubdivisionRepositoryInterface
   */
  protected $subdivisionRepository;

  /**
   * The address format repository.
   *
   * @var AddressFormatRepositoryInterface
   */
  protected $addressFormatRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article']);
    $this->adminUser = $this->drupalCreateUser([
      'create article content',
      'edit own article content',
      'administer content types',
      'administer node fields',
    ]);
    $this->drupalLogin($this->adminUser);

    // Add the address field to the article content type.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_address',
      'entity_type' => 'node',
      'type' => 'address',
    ]);
    $field_storage->save();

    $this->fieldInstance = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
      'label' => 'Address',
    ]);
    $this->fieldInstance->save();

    // Set article's form display.
    $this->formDisplay = EntityFormDisplay::load('node.article.default');
    if (!$this->formDisplay) {
      $this->formDisplay = EntityFormDisplay::create([
        'targetEntityType' => 'node',
        'bundle' => 'article',
        'mode' => 'default',
        'status' => TRUE,
      ])->save();
    }
    $this->formDisplay
      ->setComponent($this->fieldInstance->getName(), [
        'type' => 'address_default',
        'settings' => [],
      ])->save();

    $this->formContentAddUrl = 'node/add/article';
    $this->formFieldConfigUrl = 'admin/structure/types/manage/article/fields/node.article.' . $this->fieldInstance->getName();

    $this->countryRepository = \Drupal::service('address.country_repository');
    $this->subdivisionRepository = \Drupal::service('address.subdivision_repository');
    $this->addressFormatRepository = \Drupal::service('address.address_format_repository');

    $this->drupalGet($this->formContentAddUrl);
    $this->assertResponse(200, 'Article add form can be accessed at ' . $this->formContentAddUrl);

    $this->drupalGet($this->formFieldConfigUrl);
    $this->assertResponse(200, 'Address field configuration form can be accessed at ' . $this->formFieldConfigUrl);
  }

  /**
   * Test widget's initial values, default country and alter events.
   */
  function testInitialValues() {
    $fieldName = $this->fieldInstance->getName();
    $edit = [];

    // Set default country to US.
    $this->formDisplay
      ->setComponent($fieldName, [
        'type' => 'address_default',
        'settings' => [
          'default_country' => 'US',
        ],
      ])->save();

    // Optional field: Country should be optional and set to default_country.
    $this->drupalGet($this->formContentAddUrl);
    $this->assertFalse((bool) $this->xpath('//select[@name="' . $fieldName . '[0][country_code]" and boolean(@required)]'), 'Country is shown as optional.');
    $this->assertOptionSelected('edit-field-address-0-country-code', 'US', 'The configured default_country is selected.');

    // Required field: Country should be required and set to default_country.
    $this->fieldInstance->setRequired(TRUE);
    $this->fieldInstance->save();
    $this->drupalGet($this->formContentAddUrl);
    $this->assertTrue((bool) $this->xpath('//select[@name="' . $fieldName . '[0][country_code]" and boolean(@required)]'), 'Country is shown as required.');
    $this->assertOptionSelected('edit-field-address-0-country-code', 'US', 'The configured default_country is selected.');

    // Test address events.
    // The address_test module is installed here, not in setUp().
    // This way the module's events will not affect other tests.
    self::$modules[] = 'address_test';
    $container = $this->initKernel(\Drupal::request());
    $this->initConfig($container);
    $this->installModulesFromClassProperty($container);
    $this->rebuildAll();
    // Get available countries and initial values from module's event subscriber.
    $subscriber = \Drupal::service('address_test.event_subscriber');
    $availableCountries = array_keys($subscriber->getAvailableCountries());
    $initialValues = $subscriber->getInitialValues();
    // Access the content add form and test the list of countries.
    $this->drupalGet($this->formContentAddUrl);
    $options = [];
    $elements = $this->xpath('//select[@name="' . $fieldName . '[0][country_code]"]/option/@value');
    foreach ($elements as $key => $element) {
      if ($option = $element->__toString()) {
        $options[] = $option;
      }
    }
    $this->assertFieldValues($options, $availableCountries, 'Available countries set in the available countries event subscriber and present in the widget: ' . implode(', ', $options));
    // Test the values of the fields.
    foreach ($initialValues as $key => $value) {
      if ($value) {
        $this->assertFieldByName($fieldName . '[0][' . $key . ']', $value, 'Field ' . $key . ' set to initial value ' . $value . ' in form ' . $this->formContentAddUrl . ' from the initial values event subscriber.');
      }
    }
    // Remove the address_test module.
    array_pop(self::$modules);
  }

  /**
   * Test available countries.
   */
  function testAvailableCountries() {
    $fieldName = $this->fieldInstance->getName();
    $edit = [];

    // Initially, there are no available countries selected.
    // All countries from country repository should be present in the form.
    $countries = array_keys($this->countryRepository->getList());
    $this->drupalGet($this->formContentAddUrl);
    $options = [];
    $elements = $this->xpath('//select[@name="' . $fieldName . '[0][country_code]"]/option/@value');
    foreach ($elements as $key => $element) {
      if ($option = $element->__toString()) {
        $options[] = $option;
      }
    }
    $this->assertFieldValues($options, $countries, 'All countries from country repository are present in the widget.');

    // Now select some countries as available.
    $countries = ['US', 'FR', 'BR', 'JP'];
    $edit['settings[available_countries][]'] = array_map(function ($country) {
      return $country;
    }, $countries);
    $this->drupalPostForm($this->formFieldConfigUrl, $edit, t('Save settings'));
    $this->assertResponse(200);
    $this->drupalGet($this->formFieldConfigUrl);
    $options = [];
    $elements = $this->xpath('//select[@name="settings[available_countries][]"]/option[boolean(@selected)]/@value');
    foreach ($elements as $key => $element) {
      if ($option = $element->__toString()) {
        $options[] = $option;
      }
    }
    $this->assertFieldValues($options, $countries, 'Available countries set to ' . implode(', ', $countries));
    $this->drupalGet($this->formContentAddUrl);
    $options = [];
    $elements = $this->xpath('//select[@name="' . $fieldName . '[0][country_code]"]/option/@value');
    foreach ($elements as $key => $element) {
      if ($option = $element->__toString()) {
        $options[] = $option;
      }
    }
    $this->assertFieldValues($options, $countries, 'Available countries present in the widget: ' . implode(', ', $countries));
  }

  /**
   * Test item with a country that's no longer available.
   */
  function testUnavailableCountry() {
    $fieldName = $this->fieldInstance->getName();
    $address = [];
    $edit = [];

    // Select some countries as available.
    $countries = ['US', 'FR', 'BR', 'JP'];
    $edit['settings[available_countries][]'] = array_map(function ($country) {
      return $country;
    }, $countries);
    $this->drupalPostForm($this->formFieldConfigUrl, $edit, t('Save settings'));
    $this->assertResponse(200);
    $this->drupalGet($this->formFieldConfigUrl);
    $options = [];
    $elements = $this->xpath('//select[@name="settings[available_countries][]"]/option[boolean(@selected)]/@value');
    foreach ($elements as $key => $element) {
      if ($option = $element->__toString()) {
        $options[] = $option;
      }
    }
    $this->assertFieldValues($options, $countries, 'Available countries set to ' . implode(', ', $countries));

    // Create an article with one of them.
    $address[$fieldName . '[0][country_code]'] = 'US';
    $address[$fieldName . '[0][recipient]'] = 'Some Recipient';
    $address[$fieldName . '[0][organization]'] = 'Some Organization';
    $address[$fieldName . '[0][address_line1]'] = '1098 Alta Ave';
    $address[$fieldName . '[0][locality]'] = 'Mountain View';
    $address[$fieldName . '[0][administrative_area]'] = 'US-CA';
    $address[$fieldName . '[0][postal_code]'] = '94043';
    $edit = [];
    $edit[$fieldName . '[0][country_code]'] = 'US';
    $this->drupalPostAjaxForm($this->formContentAddUrl, $edit, $fieldName . '[0][country_code]');
    $this->assertResponse(200);
    $edit = $address;
    $edit['title[0][value]'] = $this->randomMachineName(8);;
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    // Check that the article is created.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertTrue($node, 'Created article ' . $edit['title[0][value]']);
    // Acccess article's edit form and check widget's values.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertResponse(200, 'Article edit form can be accessed at node/' . $node->id() . '/edit');
    $this->assertOptionSelected('edit-field-address-0-country-code', $address[$fieldName . '[0][country_code]'], 'Country code ' . $address[$fieldName . '[0][country_code]'] . ' is selected.');
    $this->assertFieldByName($fieldName . '[0][recipient]', $address[$fieldName . '[0][recipient]']);
    $this->assertFieldByName($fieldName . '[0][organization]', $address[$fieldName . '[0][organization]']);
    $this->assertFieldByName($fieldName . '[0][address_line1]', $address[$fieldName . '[0][address_line1]']);
    $this->assertFieldByName($fieldName . '[0][locality]', $address[$fieldName . '[0][locality]']);
    $this->assertOptionSelected('edit-field-address-0-administrative-area', $address[$fieldName . '[0][administrative_area]'], 'Administrative area ' . $address[$fieldName . '[0][administrative_area]'] . ' is selected.');
    $this->assertFieldByName($fieldName . '[0][postal_code]', $address[$fieldName . '[0][postal_code]']);

    // Now select some other countries as available, i.e. make 'US' unavailable country.
    $countries = ['CA', 'ES', 'AR', 'CN'];
    $edit = [];
    $edit['settings[available_countries][]'] = array_map(function ($country) {
      return $country;
    }, $countries);
    $this->drupalPostForm($this->formFieldConfigUrl, $edit, t('Save settings'));
    $this->assertResponse(200);
    $this->drupalGet($this->formFieldConfigUrl);
    $options = [];
    $elements = $this->xpath('//select[@name="settings[available_countries][]"]/option[boolean(@selected)]/@value');
    foreach ($elements as $key => $element) {
      if ($option = $element->__toString()) {
        $options[] = $option;
      }
    }
    $this->assertFieldValues($options, $countries, 'Available countries set to ' . implode(', ', $countries));

    // Acccess article's edit form and check widget's values. They should be unchanged.
    // 'US' should be in the list along with the available countries and should be selected.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertFieldByName($fieldName . '[0][recipient]', $address[$fieldName . '[0][recipient]']);
    $this->assertFieldByName($fieldName . '[0][organization]', $address[$fieldName . '[0][organization]']);
    $this->assertFieldByName($fieldName . '[0][address_line1]', $address[$fieldName . '[0][address_line1]']);
    $this->assertFieldByName($fieldName . '[0][locality]', $address[$fieldName . '[0][locality]']);
    $this->assertOptionSelected('edit-field-address-0-administrative-area', $address[$fieldName . '[0][administrative_area]'], 'Administrative area ' . $address[$fieldName . '[0][administrative_area]'] . ' is selected.');
    $this->assertFieldByName($fieldName . '[0][postal_code]', $address[$fieldName . '[0][postal_code]']);
    $this->assertOptionSelected('edit-field-address-0-country-code', $address[$fieldName . '[0][country_code]'], 'Country code ' . $address[$fieldName . '[0][country_code]'] . ' is selected.');
    $options = [];
    $elements = $this->xpath('//select[@name="' . $fieldName . '[0][country_code]"]/option/@value');
    foreach ($elements as $key => $element) {
      if ($option = $element->__toString()) {
        $options[] = $option;
      }
    }
    $countries[] = 'US';
    $this->assertFieldValues($options, $countries, 'Countries present in the widget: ' . implode(', ', $options));
  }

  /*
   * Test presence of expected fields for a country.
   */
  function testExpectedFields() {
    $fieldName = $this->fieldInstance->getName();
    // Prepare an array for easier manipulation.
    // Keys are field names from the field instance.
    // Values are corresponding field names from add article form.
    $allFields = [
      'administrativeArea' => $fieldName . '[0][administrative_area]',
      'locality' => $fieldName . '[0][locality]',
      'dependentLocality' => $fieldName . '[0][dependent_locality]',
      'postalCode' => $fieldName . '[0][postal_code]',
      'sortingCode' => $fieldName . '[0][sorting_code]',
      'addressLine1' => $fieldName . '[0][address_line1]',
      'addressLine2' => $fieldName . '[0][address_line2]',
      'organization' => $fieldName . '[0][organization]',
      'recipient' => $fieldName . '[0][recipient]',
    ];
    $edit = [];

    // Set address field required, 'default_country' setting should be set in article add form.
    // Ensure that all fields in the address settings are enabled.
    $allFieldsKeys = array_keys($allFields);
    $edit['required'] = TRUE;
    foreach ($allFieldsKeys as $field) {
      $edit['settings[fields][' . $field . ']'] = TRUE;
    }
    $this->drupalPostForm($this->formFieldConfigUrl, $edit, t('Save settings'));
    $this->assertResponse(200);
    $this->drupalGet($this->formFieldConfigUrl);
    foreach ($allFieldsKeys as $field) {
      $this->assertFieldChecked('edit-settings-fields-' . strtolower($field), 'Field ' . $field . ' is enabled.');
    }

    // Set default countries to US, FR and CN.
    // US has all fields except sorting code and dependent locality.
    // France has sorting code, and China has dependent locality, so these countries cover all fields.
    // Get expected fields from the address format repository.
    // Acccess article's add form and check the presence of the expected fields.
    foreach (['US', 'FR', 'CN'] as $country) {
      $this->formDisplay->setComponent($fieldName, [
        'type' => 'address_default',
        'settings' => [
          'default_country' => $country,
        ],
      ])->save();
      $edit = [];
      $this->drupalPostForm($this->formFieldConfigUrl, $edit, t('Save settings'));

      $addressFormat = $this->addressFormatRepository->get($country);
      $usedFields = $addressFormat->getUsedFields();
      $formFields = [];
      $this->drupalGet($this->formContentAddUrl);
      // Make one assert instead of many asserts for each field's existance.
      // Compares the address format fields array with array of address fields got from the form.
      $elements = $this->xpath('//input[starts-with(@name,"' . $fieldName . '")]/@name | //select[starts-with(@name,"' . $fieldName . '")]/@name');
      foreach ($elements as $key => $element) {
        if ($field = array_search($element->__toString(), $allFields)) {
          $formFields[] = $field;
        }
      }
      $this->assertFieldValues($usedFields, $formFields, 'Expected fields ' . implode(', ', $usedFields) . ' exists for country ' . $country . ", only found " . implode(', ', $formFields));
    }
  }

  /*
   * Test absence of fields disabled in the address settings.
   */
  function testDisabledFields() {
    $fieldName = $this->fieldInstance->getName();
    // Prepare an array for easier manipulation.
    // Keys are field names from the field instance.
    // Values are corresponding field names from add article form.
    $allFields = [
      'administrativeArea' => $fieldName . '[0][administrative_area]',
      'locality' => $fieldName . '[0][locality]',
      'dependentLocality' => $fieldName . '[0][dependent_locality]',
      'postalCode' => $fieldName . '[0][postal_code]',
      'sortingCode' => $fieldName . '[0][sorting_code]',
      'addressLine1' => $fieldName . '[0][address_line1]',
      'addressLine2' => $fieldName . '[0][address_line2]',
      'organization' => $fieldName . '[0][organization]',
      'recipient' => $fieldName . '[0][recipient]',
    ];
    $country = 'US';
    $edit = [];

    // Set default country to US.
    $this->formDisplay
      ->setComponent($fieldName, [
        'type' => 'address_default',
        'settings' => [
          'default_country' => $country,
        ],
      ])->save();

    // Set address field required, 'default_country' setting should be set in article add form.
    // First, ensure that all fields in the address settings are enabled.
    $allFieldsKeys = array_keys($allFields);
    $edit['required'] = TRUE;
    foreach ($allFieldsKeys as $field) {
      $edit['settings[fields][' . $field . ']'] = TRUE;
    }
    $this->drupalPostForm($this->formFieldConfigUrl, $edit, t('Save settings'));
    $this->assertResponse(200);
    $this->drupalGet($this->formFieldConfigUrl);
    foreach ($allFieldsKeys as $field) {
      $this->assertFieldChecked('edit-settings-fields-' . strtolower($field), 'Field ' . $field . ' is enabled.');
    }

    // Get one required and one optional field.
    $addressFormat = $this->addressFormatRepository->get($country);
    $usedFields = $addressFormat->getUsedFields();
    $requiredFields = $addressFormat->getRequiredFields();
    $optionalFields = array_diff($usedFields, $requiredFields);
    $testFields = [];
    if (!empty($requiredFields)) {
      $testFields[] = $requiredFields[key($requiredFields)];
    }
    if (!empty($optionalFields)) {
      $testFields[] = $optionalFields[key($optionalFields)];
    }

    // Create an article with all fields filled.
    $edit = [];
    $edit[$fieldName . '[0][country_code]'] = 'US';
    $edit[$fieldName . '[0][recipient]'] = 'Some Recipient';
    $edit[$fieldName . '[0][organization]'] = 'Some Organization';
    $edit[$fieldName . '[0][address_line1]'] = '1098 Alta Ave';
    $edit[$fieldName . '[0][address_line2]'] = 'Street 2';
    $edit[$fieldName . '[0][locality]'] = 'Mountain View';
    $edit[$fieldName . '[0][administrative_area]'] = 'US-CA';
    $edit[$fieldName . '[0][postal_code]'] = '94043';
    $this->drupalPostAjaxForm($this->formContentAddUrl, $edit, $fieldName . '[0][country_code]');
    $this->assertResponse(200);
    $edit['title[0][value]'] = $this->randomMachineName(8);;
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    // Check that the article is created.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertTrue($node, 'Created article ' . $edit['title[0][value]']);

    // Now disable $testFields.
    $edit = [];
    foreach ($allFieldsKeys as $field) {
      $edit['settings[fields][' . $field . ']'] = !in_array($field, $testFields);
    }
    $this->drupalPostForm($this->formFieldConfigUrl, $edit, t('Save settings'));
    $this->assertResponse(200);

    $this->drupalGet($this->formFieldConfigUrl);
    foreach ($allFieldsKeys as $field) {
      if (in_array($field, $testFields)) {
        $this->assertNoFieldChecked('edit-settings-fields-' . strtolower($field), 'Field ' . $field . ' is disabled.');
      }
      else {
        $this->assertFieldChecked('edit-settings-fields-' . strtolower($field), 'Field ' . $field . ' is enabled.');
      }
    }

    // Check absence of each $testFields in add and edit article form.
    foreach ([
               $this->formContentAddUrl,
               'node/' . $node->id() . '/edit'
             ] as $url) {
      if ($url != $this->formContentAddUrl) {
        $this->assertResponse(200, 'Article edit form can be accessed at ' . $url);
      }
      $this->assertFalse((bool) $this->xpath('//input[@name="' . implode('" or @name="', $testFields) . '"]'), 'Fields ' . implode(', ', $testFields) . ' are absent in the form ' . $url);
    }
  }

  /*
   * Test presence of subdivision dropdowns where expected.
   */
  function testSubdivision() {
    $fieldName = $this->fieldInstance->getName();
    $edit = [];

    // Set address field required, 'default_country' setting should be set in article add form.
    $edit['required'] = TRUE;
    $this->drupalPostForm($this->formFieldConfigUrl, $edit, t('Save settings'));
    $this->assertResponse(200);

    // Pick some countries for subdivision test.
    // US has states, Brazil has states and cities, China has provinces, cities and districts.
    foreach (['US', 'BR', 'CN'] as $country) {
      // Set default country to country.
      $this->formDisplay
        ->setComponent($fieldName, [
          'type' => 'address_default',
          'settings' => [
            'default_country' => $country,
          ],
        ])->save();
      $edit = [];
      $this->drupalPostForm($this->formFieldConfigUrl, $edit, t('Save settings'));

      $administrativeAreas = $this->subdivisionRepository->getList($country);
      $localities = [];
      $dependentLocalities = [];
      $keys = array_keys($administrativeAreas);
      $depth = $this->subdivisionRepository->getDepth($country);
      // On article add form there should be an administrative area dropdown.
      $this->drupalGet($this->formContentAddUrl);
      $options = [];
      $elements = $this->xpath('//select[@name="' . $fieldName . '[0][administrative_area]"]/option/@value');
      foreach ($elements as $key => $element) {
        if ($option = $element->__toString()) {
          $options[] = $option;
        }
      }
      $this->assertFieldValues($options, $keys, 'All administrative areas for country ' . $country . ' are present in the widget.');
      if ($depth > 1) {
        $administrativeArea = '';
        $locality = '';
        // Find localities in second level.
        foreach ($keys as $administrativeArea) {
          if ($localities = $this->subdivisionRepository->getList($country, $administrativeArea)) {
            break;
          }
        }
        if (!empty($localities)) {
          $keys = array_keys($localities);
          $edit = [];
          $edit[$fieldName . '[0][administrative_area]'] = $administrativeArea;
          $this->drupalPostAjaxForm($this->formContentAddUrl, $edit, $fieldName . '[0][administrative_area]');
          $this->assertResponse(200);
          $this->assertOptionSelectedWithDrupalSelector('edit-field-address-0-administrative-area', $administrativeArea, 'Administrative area ' . $administrativeAreas[$administrativeArea] . ' selected for country ' . $country);
          $options = [];
          $elements = $this->xpath('//select[@name="' . $fieldName . '[0][locality]"]/option/@value');
          foreach ($elements as $key => $element) {
            if ($option = $element->__toString()) {
              $options[] = $option;
            }
          }
          $this->assertFieldValues($options, $keys, 'All localities for administrative area ' . $administrativeAreas[$administrativeArea] . ' of country ' . $country . ' are present in the widget.');
          if ($depth > 2) {
            // Find dependent localities in third level.
            foreach ($keys as $locality) {
              if ($dependentLocalities = $this->subdivisionRepository->getList($country, $locality)) {
                break;
              }
            }
            if (!empty($dependentLocalities)) {
              $keys = array_keys($dependentLocalities);
              $edit[$fieldName . '[0][locality]'] = $locality;
              $this->drupalPostAjaxForm(NULL, $edit, $fieldName . '[0][locality]');
              $this->assertResponse(200);
              $this->assertOptionSelectedWithDrupalSelector('edit-field-address-0-locality', $locality, 'Locality ' . $localities[$locality] . ' of administrative area ' . $administrativeAreas[$administrativeArea] . ' selected for country ' . $country);
              $options = [];
              $elements = $this->xpath('//select[@name="' . $fieldName . '[0][dependent_locality]"]/option/@value');
              foreach ($elements as $key => $element) {
                if ($option = $element->__toString()) {
                  $options[] = $option;
                }
              }
              $this->assertFieldValues($options, $keys, 'All dependent localities for locality ' . $localities[$locality] . ' of administrative area ' . $administrativeAreas[$administrativeArea] . ' of country ' . $country . ' are present in the widget.');
            }
          }
        }
      }
    }
  }

  /**
   * Test that changing the country clears the expected values.
   */
  function testClearValues() {
    $fieldName = $this->fieldInstance->getName();
    // Set the default country to US.
    $this->formDisplay->setComponent($fieldName, [
      'type' => 'address_default',
      'settings' => [
        'default_country' => 'US',
      ],
    ])->save();
    // Make the field required.
    $edit = [];
    $edit['required'] = TRUE;
    $this->drupalPostForm($this->formFieldConfigUrl, $edit, t('Save settings'));

    // Create an article with all fields filled.
    $edit = [];
    $edit[$fieldName . '[0][country_code]'] = 'US';
    $edit[$fieldName . '[0][recipient]'] = 'Some Recipient';
    $edit[$fieldName . '[0][organization]'] = 'Some Organization';
    $edit[$fieldName . '[0][address_line1]'] = '1098 Alta Ave';
    $edit[$fieldName . '[0][address_line2]'] = 'Street 2';
    $edit[$fieldName . '[0][locality]'] = 'Mountain View';
    $edit[$fieldName . '[0][administrative_area]'] = 'US-CA';
    $edit[$fieldName . '[0][postal_code]'] = '94043';
    $this->drupalPostAjaxForm($this->formContentAddUrl, $edit, $fieldName . '[0][country_code]');
    $this->assertResponse(200);
    $edit['title[0][value]'] = $this->randomMachineName(8);;
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    // Check that the article has been created.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertTrue($node, 'Created article ' . $edit['title[0][value]']);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertFieldByName($fieldName . '[0][country_code]', 'US', 'Country code set to US in form node/' . $node->id() . '/edit');
    $this->assertFieldByName($fieldName . '[0][administrative_area]', 'US-CA', 'Field administrative_area set to US-CA in form node/' . $node->id() . '/edit');
    $this->assertFieldByName($fieldName . '[0][locality]', '', 'Field locality set to Moutain View in form node/' . $node->id() . '/edit');
    $this->assertFieldByName($fieldName . '[0][postal_code]', '', 'Field postal_code set to 94043 in form node/' . $node->id() . '/edit');

    // Now change the country to China, subdivision fields should be cleared.
    $edit = [];
    $edit[$fieldName . '[0][country_code]'] = 'CN';
    $this->drupalPostAjaxForm('node/' . $node->id() . '/edit', $edit, $fieldName . '[0][country_code]');
    $this->assertResponse(200);
    // Check that values are cleared.
    $this->assertFieldByName($fieldName . '[0][country_code]', 'CN', 'Country code changed to CN in form node/' . $node->id() . '/edit');
    $this->assertFieldByName($fieldName . '[0][administrative_area]', '', 'Field administrative_area is cleared in form node/' . $node->id() . '/edit');
    $this->assertFieldByName($fieldName . '[0][locality]', '', 'Field locality is cleared in form node/' . $node->id() . '/edit');
    $this->assertFieldByName($fieldName . '[0][dependent_locality]', '', 'Field dependent_locality is cleared in form node/' . $node->id() . '/edit');
    $this->assertFieldByName($fieldName . '[0][postal_code]', '', 'Field postal_code is cleared in form node/' . $node->id() . '/edit');

    // Test the same with France.
    $edit = [];
    $edit[$fieldName . '[0][country_code]'] = 'FR';
    $this->drupalPostAjaxForm(NULL, $edit, $fieldName . '[0][country_code]');
    $this->assertResponse(200);
    // Check that values are cleared.
    $this->assertFieldByName($fieldName . '[0][country_code]', 'FR', 'Country code changed to FR in form node/' . $node->id() . '/edit');
    $this->assertFieldByName($fieldName . '[0][locality]', '', 'Field locality is cleared in form node/' . $node->id() . '/edit');
    $this->assertFieldByName($fieldName . '[0][postal_code]', '', 'Field postal_code is cleared in form node/' . $node->id() . '/edit');
    $this->assertFieldByName($fieldName . '[0][sorting_code]', '', 'Field sorting_code is cleared in form node/' . $node->id() . '/edit');
  }

  /**
   * Asserts that the passed field values are correct.
   *
   * Ignores differences in ordering.
   *
   * @param array $fieldValues
   *   The field values.
   * @param array $expectedValues
   *   The expected values.
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   */
  protected function assertFieldValues(array $fieldValues, array $expectedValues, $message = '') {
    $valid = TRUE;
    if (count($fieldValues) == count($expectedValues)) {
      foreach ($expectedValues as $value) {
        if (!in_array($value, $fieldValues)) {
          $valid = FALSE;
          break;
        }
      }
    }
    else {
      $valid = FALSE;
    }

    $this->assertTrue($valid, $message);
  }

}
