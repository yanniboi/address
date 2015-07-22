<?php

/**
 * @file
 * Contains \Drupal\address\Tests\Formatter\DefaultFormatterTest.
 */

namespace Drupal\address\Tests\Formatter;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests the default formatter.
 *
 * @group address
 */
class DefaultFormatterTest extends KernelTestBase {

  /**
   * @var array
   */
  public static $modules = ['system', 'field', 'text', 'entity_test', 'user', 'address'];

  /**
   * @var string
   */
  protected $entityType;

  /**
   * @var string
   */
  protected $bundle;

  /**
   * @var string
   */
  protected $fieldName;

  /**
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $display;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['system']);
    $this->installConfig(['field']);
    $this->installConfig(['text']);
    $this->installConfig(['address']);
    $this->installEntitySchema('entity_test');

    $this->entityType = 'entity_test';
    $this->bundle = $this->entityType;
    $this->fieldName = Unicode::strtolower($this->randomMachineName());

    $field_storage = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => $this->entityType,
      'type' => 'address',
    ]);
    $field_storage->save();

    $instance = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $this->bundle,
      'label' => $this->randomMachineName(),
    ]);
    $instance->save();

    $this->display = entity_get_display($this->entityType, $this->bundle, 'default');
    $this->display->setComponent($this->fieldName, [
      'type' => 'address_default',
      'settings' => [],
    ]);
    $this->display->save();
  }

  /**
   *
   */
  public function testElSalvadorAddress() {
    $entity = EntityTest::create([]);
    $entity->set($this->fieldName, [
      'country_code' => 'SV',
      'administrative_area' => 'Ahuachapán',
      'address_line1' => 'Some Street 12',
      'locality' => 'Ahuachapán',
      'country' => 'El Salvador',
      'postal_code' => 'CP 111',
      'recipient' => 'Hugo Sanchez',
    ]);
    $rendered = $this->renderEntityFields($entity, $this->display);
    var_dump($rendered);
    $expected = SafeMarkup::format('@line1@line2@line3@line4@line5@line6', [
      '@line1' => '<p translate="no">',
      '@line2' => '<span class="address-line1">Some Street 12</span><br>',
      '@line3' => '<span class="locality">Ahuachapán</span><br>',
      '@line4' => '<span class="administrative-area">Ahuachapán</span><br>',
      '@line5' => '<span class="country">El Salvador</span>',
      '@line6' => '</p>',
    ]);
    $this->assertRaw($expected);
  }

  /**
   * Renders fields of a given entity with a given display.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity object with attached fields to render.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The display to render the fields in.
   *
   * @return string
   *   The rendered entity fields.
   */
  protected function renderEntityFields(FieldableEntityInterface $entity, EntityViewDisplayInterface $display) {
    $content = $display->build($entity);
    $content = $this->render($content);
    return $content;
  }

}
