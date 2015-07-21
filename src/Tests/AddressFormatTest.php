<?php

/**
 * @file
 * Contains \Drupal\address\Tests\AddressFormatTest.
 */

namespace Drupal\address\Tests;

use CommerceGuys\Addressing\Repository\AddressFormatRepository;
use Drupal\address\Entity\AddressFormat;
use Drupal\Core\Locale\CountryManager;
use Drupal\simpletest\WebTestBase;

/**
 * Ensures that address format functions work correctly.
 *
 * @group address
 */
class AddressFormatTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'user', 'address'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Utility function to create a random address format.
   *
   * @return AddressFormat A random address format config entity.
   */
  protected function createRandomAddressFormat() {
    $countryCodes = array_keys(CountryManager::getStandardList());

    // Find a random country code that doesn't exist yet.
    while ($key = array_rand($countryCodes)) {
      if (entity_load('address_format', $countryCodes[$key])) {
        continue;
      }
      $countryCode = $countryCodes[$key];
      break;
    }

    $values = [
      'countryCode' => $countryCode,
    ];

    $addressFormat = AddressFormat::create($values);

    $addressFormat->save();
    return $addressFormat;
  }

  /**
   * Tests creating a address format programmatically.
   */
  function testAddressFormatCreationProgrammatically() {
    // Create a address format type programmatically.
    $addressFormat = $this->createRandomAddressFormat();
    $addressFormatExists = (bool) entity_load('address_format', $addressFormat->id());
    $this->assertTrue($addressFormatExists, 'The new address format has been created in the database.');

    // Login a test user.
    $webUser = $this->drupalCreateUser(['administer address formats']);
    $this->drupalLogin($webUser);
    // Visit the address format edit page.
    $this->drupalGet('admin/config/regional/address-formats/manage/' . $addressFormat->id());
    $this->assertResponse(200, 'The new address format can be accessed at admin/config/regional/address-formats.');
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

}
