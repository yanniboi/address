<?php

namespace Drupal\address;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;

class ConstraintViolationAssertion
{
  /**
   * @var ExecutionContextInterface
   */
  private $context;

  /**
   * @var ConstraintViolationAssertion[]
   */
  private $assertions;

  private $message;
  private $parameters = array();
  private $invalidValue = 'InvalidValue';
  private $propertyPath = 'property.path';
  private $translationDomain;
  private $plural;
  private $code;
  private $constraint;
  private $cause;

  public function __construct(ExecutionContextInterface $context, $message, Constraint $constraint = null, array $assertions = array())
  {
    $this->context = $context;
    $this->message = $message;
    $this->constraint = $constraint;
    $this->assertions = $assertions;
  }

  public function atPath($path)
  {
    $this->propertyPath = $path;

    return $this;
  }

  public function setParameter($key, $value)
  {
    $this->parameters[$key] = $value;

    return $this;
  }

  public function setParameters(array $parameters)
  {
    $this->parameters = $parameters;

    return $this;
  }

  public function setTranslationDomain($translationDomain)
  {
    $this->translationDomain = $translationDomain;

    return $this;
  }

  public function setInvalidValue($invalidValue)
  {
    $this->invalidValue = $invalidValue;

    return $this;
  }

  public function setPlural($number)
  {
    $this->plural = $number;

    return $this;
  }

  public function setCode($code)
  {
    $this->code = $code;

    return $this;
  }

  public function setCause($cause)
  {
    $this->cause = $cause;

    return $this;
  }

  public function buildNextViolation($message)
  {
    $assertions = $this->assertions;
    $assertions[] = $this;

    return new self($this->context, $message, $this->constraint, $assertions);
  }

  public function assertRaised()
  {
    $expected = array();
    foreach ($this->assertions as $assertion) {
      $expected[] = $assertion->getViolation();
    }
    $expected[] = $this->getViolation();

    $violations = iterator_to_array($this->context->getViolations());

    \PHPUnit_Framework_Assert::assertSame($expectedCount = count($expected), $violationsCount = count($violations), sprintf('%u violation(s) expected. Got %u.', $expectedCount, $violationsCount));

    reset($violations);

    foreach ($expected as $violation) {
      \PHPUnit_Framework_Assert::assertEquals($violation, current($violations));
      next($violations);
    }
  }

  private function getViolation()
  {
    return new ConstraintViolation(
      null,
      $this->message,
      $this->parameters,
      $this->context->getRoot(),
      $this->propertyPath,
      $this->invalidValue,
      $this->plural,
      $this->code,
      $this->constraint,
      $this->cause
    );
  }
}