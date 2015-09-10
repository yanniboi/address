<?php

/**
 * @file
 * Contains \Drupal\address\Tests\AddressFormatTest.
 */

namespace Drupal\address\Tests;

use CommerceGuys\Addressing\Repository\AddressFormatRepository;
use Drupal\address\Entity\AddressFormat;
use Drupal\simpletest\WebTestBase;
use Drupal\Core\Entity\EntityStorageException;

/**
 * Tests the address format entity and UI.
 *
 * @group address
 */
class AddressFormatTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'address',
  ];

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer address formats',
      'access administration pages',
      'administer site configuration',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test importing address formats using service.
   */
  function testAddressFormatImport() {
    $externalRepository = new AddressFormatRepository();
    $externalCount = count($externalRepository->getAll());
    $count = \Drupal::entityQuery('address_format')->count()->execute();
    $this->assertEqual($externalCount, $count, 'All address formats imported at installation.');
  }

  /**
   * Tests creating a address format via a form and programmatically.
   */
  function testAddressFormatCreation() {
    $countryCode = 'CM';
    $values = [
      'countryCode' => $countryCode,
      'format' => '%locality',
      'localityType' => 'city',
    ];
    $addressFormat = AddressFormat::create($values);
    $addressFormat->save();
    $this->drupalGet('admin/config/regional/address-formats/manage/' . $addressFormat->id());
    $this->assertResponse(200, 'The new address format can be accessed at admin/config/regional/address-formats.');

    $addressFormat = AddressFormat::load($countryCode);
    $this->assertEqual($addressFormat->getCountryCode(), $values['countryCode'], 'The new address format has the correct countryCode.');
    $this->assertEqual($addressFormat->getFormat(), $values['format'], 'The new address format has the correct format string.');
    $this->assertEqual($addressFormat->getLocalityType(), $values['localityType'], 'The new address format has the correct localityType.');

    $countryCode = 'YE';
    $edit = [
      'countryCode' => $countryCode,
      'format' => '%locality',
      'localityType' => 'city',
    ];
    $this->drupalGet('admin/config/regional/address-formats/add');
    $this->assertResponse(200, 'The address format add form can be accessed at admin/config/regional/address-formats/add.');
    $this->drupalPostForm('admin/config/regional/address-formats/add', $edit, t('Save'));

    $addressFormat = AddressFormat::load($countryCode);
    $this->assertEqual($addressFormat->getCountryCode(), $edit['countryCode'], 'The new address format has the correct countryCode.');
    $this->assertEqual($addressFormat->getFormat(), $edit['format'], 'The new address format has the correct format string.');
    $this->assertEqual($addressFormat->getLocalityType(), $edit['localityType'], 'The new address format has the correct localityType.');
  }

  /**
   * Tests editing a address format via a form.
   */
  function testAddressFormatEditing() {
    $countryCode = 'RS';
    $addressFormat = AddressFormat::load($countryCode);
    $newPostalCodeType = ($addressFormat->getPostalCodeType() == 'zip') ? 'postal' : 'zip';
    $edit = [
      'postalCodeType' => $newPostalCodeType,
    ];
    $this->drupalPostForm('admin/config/regional/address-formats/manage/' . $countryCode, $edit, t('Save'));

    $addressFormat = AddressFormat::load($countryCode);
    $this->assertEqual($addressFormat->getPostalCodeType(), $newPostalCodeType, 'The address format PostalCodeType has been changed.');
  }

  /**
   * Tests deleting a address format via a form.
   */
  public function testAddressFormatDeletion() {
    $countryCode = 'RS';
    $this->drupalGet('admin/config/regional/address-formats/manage/' . $countryCode . '/delete');
    $this->assertResponse(200, 'The address format delete form can be accessed at admin/config/regional/address-formats/manage.'
      . $countryCode . '/delete');
    $this->assertText(t('This action cannot be undone.'), 'The address format delete confirmation form is available');
    $this->drupalPostForm(NULL, NULL, t('Delete'));

    $addressFormatExists = (bool) AddressFormat::load($countryCode);
    $this->assertFalse($addressFormatExists, 'The address format has been deleted form the database.');
  }

  /**
   * Tests deleting a address format for countryCode = ZZ via a form and from the API.
   */
  function testAddressFormatDeleteZZ() {
    $countryCode = 'ZZ';
    $this->drupalGet('admin/config/regional/address-formats/manage/' . $countryCode . '/delete');
    $this->assertResponse(403, "The delete form for the 'ZZ' address format cannot be accessed.");
    // Try deleting ZZ from the API
    $addressFormat = AddressFormat::load($countryCode);
    try {
      $addressFormat->delete();
      $this->fail("The 'ZZ' address format can't be deleted.");
    }
    catch (EntityStorageException $e) {
      $this->assertEqual("The 'ZZ' address format can't be deleted.", $e->getMessage());
    }
  }

}
