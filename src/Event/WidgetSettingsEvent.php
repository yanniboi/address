<?php

/**
 * @file
 * Contains \Drupal\address\Event\WidgetSettingsEvent.
 */

namespace Drupal\address\Event;

use Drupal\Core\Field\FieldDefinitionInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the widget settings event.
 *
 * @see \Drupal\address\Event\AddressEvents
 * @see \Drupal\address\Plugin\Field\FieldWidget\AddressDefaultWidget::defaultSettings
 */
class WidgetSettingsEvent extends Event {

  /**
   * The widget settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * The field definition.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $fieldDefinition;

  /**
   * Constructs a new WidgetSettingsEvent object.
   *
   * @param array $settings
   *   The widget settings.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   The field definition.
   */
  public function __construct(array $settings, FieldDefinitionInterface $fieldDefinition) {
    $this->settings = $settings;
    $this->fieldDefinition = $fieldDefinition;
  }

  /**
   * Gets the widget settings.
   *
   * @return array
   *   The widget settings.
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * Sets the widget settings.
   *
   * @return $this
   */
  public function setSettings(array $settings) {
    $this->settings = $settings;
    return $this;
  }

  /**
   * Gets the field definition.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The field definition.
   */
  public function getFieldDefinition() {
    return $this->fieldDefinition;
  }

}

