<?php

/**
 * @file
 * Contains \Drupal\address\ZoneMemberInterface.
 */

namespace Drupal\address;

use CommerceGuys\Zone\Model\ZoneMemberInterface as ExternalZoneMemberInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Defines the interface for zone memberss.
 */
interface ZoneMemberInterface extends ExternalZoneMemberInterface, ConfigurablePluginInterface, PluginFormInterface {
}
