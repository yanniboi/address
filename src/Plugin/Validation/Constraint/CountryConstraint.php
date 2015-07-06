<?php

/**
 * @file
 * Contains \Drupal\address\Plugin\Validation\Constraint\CountryConstraint.
 */

namespace Drupal\address\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Country constraint.
 *
 * @Constraint(
 *   id = "Country",
 *   label = @Translation("Country", context = "Validation"),
 *   type = { "address" }
 * )
 */
class CountryConstraint extends Constraint {

  public $message = 'This value is not a valid country.';

}
