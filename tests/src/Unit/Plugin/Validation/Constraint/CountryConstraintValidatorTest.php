<?php

/**
 * @file
 * Contains \Drupal\Tests\address\Plugin\Validation\Constraint\CountryConstraintValidatorTest.
 */

namespace Drupal\Tests\address\Unit\Plugin\Validation\Constraint;

use Drupal\address\Plugin\Validation\Constraint\CountryConstraint;
use Drupal\address\Plugin\Validation\Constraint\CountryConstraintValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContext;
use Drupal\address\ConstraintViolationAssertion;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\address\Plugin\Validation\Constraint\CountryConstraintValidator
 * @group address
 */
class CountryConstraintValidatorTest extends UnitTestCase {

  /**
   * The constraint.
   *
   * @var \Drupal\address\Plugin\Validation\Constraint\CountryConstraint
   */
  protected $constraint;

  /**
   * @var ConstraintValidatorInterface
   */
  protected $validator;


  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->constraint = new CountryConstraint(['availableCountries' => ['FR']]);

    // The following code is copied from the parent setUp(), which isn't
    // called to avoid the call to \Locale, which introduces a dependency
    // on the intl extension (or symfony/intl).
    $this->group = 'MyGroup';
    $this->metadata = NULL;
    $this->object = NULL;
    $this->value = 'InvalidValue';
    $this->root = 'root';
    $this->propertyPath = '';
    $this->context = $this->createContext();
    $this->validator = $this->createValidator();
    $this->validator->initialize($this->context);
  }


  protected function createContext()
  {
    $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
    $validator = $this->getMock('Symfony\Component\Validator\Validator\ValidatorInterface');
    $contextualValidator = $this->getMock('Symfony\Component\Validator\Validator\ContextualValidatorInterface');

    $context = new ExecutionContext($validator, $this->root, $translator);
    $context->setGroup($this->group);
    $context->setNode($this->value, $this->object, $this->metadata, $this->propertyPath);
    $context->setConstraint($this->constraint);

    $validator->expects($this->any())
      ->method('inContext')
      ->with($context)
      ->will($this->returnValue($contextualValidator));

    return $context;
  }


  protected function createValidator() {
    $country_repository = $this->getMock('CommerceGuys\Addressing\Repository\CountryRepositoryInterface');
    $country_repository->expects($this->any())
      ->method('getList')
      ->willReturn(['FR' => 'France', 'RS' => 'Serbia']);

    return new CountryConstraintValidator($country_repository);
  }

  /**
   *
   */
  protected function assertNoViolation()
  {
    $this->assertSame(0, $violationsCount = count($this->context->getViolations()), sprintf('0 violation expected. Got %u.', $violationsCount));
  }

  /**
   * @param $message
   *
   * @return ConstraintViolationAssertion
   */
  protected function buildViolation($message)
  {
    return new ConstraintViolationAssertion($this->context, $message, $this->constraint);
  }

  /**
   * @covers ::validate
   */
  public function testEmptyIsValid() {
    $this->validator->validate($this->getMockAddress(NULL), $this->constraint);
    $this->assertNoViolation();

    $this->validator->validate($this->getMockAddress(''), $this->constraint);
    $this->assertNoViolation();
  }

  /**
   * @covers ::validate
   *
   * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
   */
  public function testInvalidValueType() {
    $this->validator->validate(new \stdClass(), $this->constraint);
  }

  /**
   * @covers ::validate
   */
  public function testInvalidCountry() {
    $this->validator->validate($this->getMockAddress('InvalidValue'), $this->constraint);
    $this->buildViolation($this->constraint->invalidMessage)
      ->setParameters(['%value' => '"InvalidValue"'])
      ->atPath('country_code')
      ->assertRaised();
  }

  /**
   * @covers ::validate
   */
  public function testNotAvailableCountry() {
    $this->validator->validate($this->getMockAddress('RS'), $this->constraint);
    $this->buildViolation($this->constraint->notAvailableMessage)
      ->setParameters(['%value' => '"RS"'])
      ->atPath('country_code')
      ->assertRaised();
  }

  /**
   * @covers ::validate
   */
  public function testValidCountry() {
    $this->validator->validate($this->getMockAddress('FR'), $this->constraint);
    $this->assertNoViolation();
  }

  /**
   * Gets a mock address.
   *
   * @param string $country_code
   *   The country code to return via $address->getCountryCode().
   *
   * @return \Drupal\address\AddressInterface|\PHPUnit_Framework_MockObject_MockObject
   *   The mock address.
   */
  protected function getMockAddress($country_code) {
    $address = $this->getMockBuilder('Drupal\address\AddressInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $address->expects($this->any())
      ->method('getCountryCode')
      ->willReturn($country_code);

    return $address;
  }

}
