<?php

/**
 * @file
 * Contains \Drupal\address\Plugin\Validation\Constraint\CountryConstraintValidator.
 */

namespace Drupal\address\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use CommerceGuys\Addressing\Repository\CountryRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates the country constraint.
 */
class CountryConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The country repository.
   *
   * @var \CommerceGuys\Addressing\Repository\CountryRepositoryInterface
   */
  protected $countryRepository;

  /**
   * Constructs a new CountryConstraintValidator object.
   *
   * @param \CommerceGuys\Addressing\Repository\CountryRepositoryInterface $countryRepository
   *   The country repository.
   */
  public function __construct(CountryRepositoryInterface $countryRepository) {
    $this->countryRepository = $countryRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('address.country_repository'));
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if ($value === NULL || $value === '') {
      return;
    }

    if (!is_scalar($value) && !(is_object($value) && method_exists($value, '__toString'))) {
      throw new UnexpectedTypeException($value, 'string');
    }

    $countries = $this->countryRepository->getList();
    $value = (string) $value;

    if (!isset($countries[$value])) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('%value', $this->formatValue($value))
        ->addViolation();
    }
  }

}
