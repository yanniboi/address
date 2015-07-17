<?php

/**
 * @file
 * Contains \Drupal\address\Plugin\Validation\Constraint\AddressFormatConstraint.
 */

namespace Drupal\address\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Address format constraint.
 *
 * @Constraint(
 *   id = "AddressFormat",
 *   label = @Translation("Address Format", context = "Validation"),
 *   type = { "address" }
 * )
 */
class AddressFormatConstraint extends Constraint {

  public $fields;
  public $blankMessage = 'This value should be blank';
  public $notBlankMessage = 'This value should not be blank';
  public $invalidMessage = 'This value is invalid.';

  /**
   * {@inheritDoc}
   */
  public function __construct($options = NULL) {
    parent::__construct($options);

    // Validate all fields by default.
    if (empty($this->fields)) {
      $this->fields = array_values(AddressField::getAll());
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getTargets() {
    return self::CLASS_CONSTRAINT;
  }

}
