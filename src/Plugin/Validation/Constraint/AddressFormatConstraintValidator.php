<?php

/**
 * @file
 * Contains \Drupal\address\Plugin\Validation\Constraint\AddressFormatConstraintValidator.
 */

namespace Drupal\address\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use CommerceGuys\Addressing\Repository\CountryRepositoryInterface;
use CommerceGuys\Addressing\Validator\Constraints\AddressFormatValidator as ExternalValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates the address format constraint.
 */
class AddressFormatConstraintValidator extends ExternalValidator implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('address.address_format_repository'),
      $container->get('address.subdivision_repository')
    );
  }

}
