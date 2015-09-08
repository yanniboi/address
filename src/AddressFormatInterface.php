<?php

/**
 * @file
 * Contains \Drupal\address\AddressFormatInterface.
 */

namespace Drupal\address;

use CommerceGuys\Addressing\Model\AddressFormatEntityInterface as ExternalAddressFormatInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for address formats.
 */
interface AddressFormatInterface extends ExternalAddressFormatInterface, ConfigEntityInterface {
}
