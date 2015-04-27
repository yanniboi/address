<?php

/**
 * @file
 * Contains \Drupal\address\Form\AddressFormatFormDeleteForm.
 */

namespace Drupal\address\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Locale\CountryManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete an address format.
 */
class AddressFormatDeleteForm extends EntityDeleteForm {

  /**
   * The country manager.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManager;

  /**
   * Creates an AddressFormatDeleteForm instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The address format storage.
   */
  public function __construct(CountryManagerInterface $countryManager) {
    $this->countryManager = $countryManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('countryManager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $countries = $this->countryManager->getList();
    $addressFormat = $this->getEntity();

    return $this->t('Are you sure you want to delete the address format for %country?', array(
      '%country' => $countries[$addressFormat->getCountryCode()],
    ));
  }

}
