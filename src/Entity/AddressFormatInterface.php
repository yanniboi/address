<?php

/**
 * @file
 * Contains \Drupal\address\Entity\AddressFormatInterface.
 */

namespace Drupal\address\Entity;

use CommerceGuys\Addressing\Model\AddressFormatEntityInterface as ExternalAddressFormatInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for address formats.
 */
interface AddressFormatInterface extends ExternalAddressFormatInterface, ConfigEntityInterface {
}
