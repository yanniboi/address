<?php

/**
 * @file
 * Contains Drupal\address\Form\AddressFormatForm.
 */

namespace Drupal\address\Form;

use CommerceGuys\Addressing\Enum\AddressField;
use CommerceGuys\Intl\Country\CountryRepositoryInterface;
use Drupal\address\LabelHelper;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AddressFormatForm extends EntityForm {

  /**
   * The address format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The country repository.
   *
   * @var \CommerceGuys\Intl\Country\CountryRepositoryInterface
   */
  protected $countryRepository;

  /**
   * Creates an AddressFormatForm instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The address format storage.
   * @param \CommerceGuys\Intl\Country\CountryRepositoryInterface $countryRepository
   *   The country repository.
   */
  public function __construct(EntityStorageInterface $storage, CountryRepositoryInterface $countryRepository) {
    $this->storage = $storage;
    $this->countryRepository = $countryRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityManagerInterface $entityManager */
    $entityManager = $container->get('entity.manager');

    return new static($entityManager->getStorage('address_format'), $container->get('address.country_repository'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $formState) {
    $form = parent::form($form, $formState);
    $addressFormat = $this->entity;
    $countryCode = $addressFormat->getCountryCode();
    if ($countryCode == 'ZZ') {
      $form['countryCode'] = [
        '#type' => 'item',
        '#title' => $this->t('Country'),
        '#markup' => $this->t('Generic'),
      ];
    }
    else {
      $form['countryCode'] = [
        '#type' => 'select',
        '#title' => $this->t('Country'),
        '#default_value' => $addressFormat->getCountryCode(),
        '#required' => TRUE,
        '#options' => $this->countryRepository->getList(),
        '#disabled' => !$addressFormat->isNew(),
      ];
    }

    $form['format'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Format'),
      '#description' => $this->t('Available tokens: @tokens', ['@tokens' => implode(', ', AddressField::getTokens())]),
      '#default_value' => $addressFormat->getFormat(),
      '#required' => TRUE,
    ];
    $form['requiredFields'] = [
      '#type' => 'checkboxes',
      '#title' => t('Required fields'),
      '#options' => LabelHelper::getGenericFieldLabels(),
      '#default_value' => $addressFormat->getRequiredFields(),
    ];
    $form['uppercaseFields'] = [
      '#type' => 'checkboxes',
      '#title' => t('Uppercase fields'),
      '#description' => t('Uppercased on envelopes to facilitate automatic post handling.'),
      '#options' => LabelHelper::getGenericFieldLabels(),
      '#default_value' => $addressFormat->getUppercaseFields(),
    ];
    $form['postalCodePattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Postal code pattern'),
      '#description' => $this->t('Regular expression used to validate postal codes.'),
      '#default_value' => $addressFormat->getPostalCodePattern(),
    ];
    $form['postalCodePrefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Postal code prefix'),
      '#description' => $this->t('Added to postal codes when formatting an address for international mailing.'),
      '#default_value' => $addressFormat->getPostalCodePrefix(),
      '#size' => 5,
    ];

    $form['postalCodeType'] = [
      '#type' => 'select',
      '#title' => $this->t('Postal code type'),
      '#default_value' => $addressFormat->getPostalCodeType(),
      '#options' =>  LabelHelper::getPostalCodeLabels(),
      '#empty_value' => '',
    ];
    $form['dependentLocalityType'] = [
      '#type' => 'select',
      '#title' => $this->t('Dependent locality type'),
      '#default_value' => $addressFormat->getDependentLocalityType(),
      '#options' => LabelHelper::getDependentLocalityLabels(),
      '#empty_value' => '',
    ];
    $form['localityType'] = [
      '#type' => 'select',
      '#title' => $this->t('Locality type'),
      '#default_value' => $addressFormat->getLocalityType(),
      '#options' => LabelHelper::getLocalityLabels(),
      '#empty_value' => '',
    ];
    $form['administrativeAreaType'] = [
      '#type' => 'select',
      '#title' => $this->t('Administrative area type'),
      '#default_value' => $addressFormat->getAdministrativeAreaType(),
      '#options' => LabelHelper::getAdministrativeAreaLabels(),
      '#empty_value' => '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $formState) {
    parent::validate($form, $formState);

    // Disallow adding an address format for a country that already has one.
    if ($this->entity->isNew()) {
      $country = $formState->getValue('countryCode');
      if ($this->storage->load($country)) {
        $formState->setErrorByName('countryCode', $this->t('The selected country already has an address format.'));
      }
    }

    // Require the matching type field for the fields specified in the format.
    $format = $formState->getValue('format');
    $requirements = [
      '%postalCode' => 'postalCodeType',
      '%dependentLocality' => 'dependentLocalityType',
      '%locality' => 'localityType',
      '%administrativeArea' => 'administrativeAreaType',
    ];
    foreach ($requirements as $token => $requiredField) {
      if (strpos($format, $token) !== FALSE && !$formState->getValue($requiredField)) {
        $title = $form[$requiredField]['#title'];
        $formState->setErrorByName($requiredField, $this->t('%title is required.', ['%title' => $title]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $formState) {
    $addressFormat = $this->entity;
    $addressFormat->save();
    drupal_set_message($this->t('Saved the %label address format.', [
      '%label' => $addressFormat->label(),
    ]));
    $formState->setRedirectUrl($addressFormat->urlInfo('collection'));
  }

}
