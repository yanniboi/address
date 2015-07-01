<?php

/**
 * @file
 * Contains \Drupal\address\Event\AddressEvents.
 */

namespace Drupal\address\Event;

/**
 * Defines events for the address module.
 */
final class AddressEvents {

  /**
   * Name of the event fired when altering widget settings.
   *
   * @Event
   *
   * @see \Drupal\address\Event\WidgetSettingsEvent
   */
  const WIDGET_SETTINGS = 'address.widget.settings';

  /**
   * Name of the event fired when altering initial values.
   *
   * @Event
   *
   * @see \Drupal\address\Event\InitialValuesEvent
   */
  const INITIAL_VALUES = 'address.widget.initial_values';

}
