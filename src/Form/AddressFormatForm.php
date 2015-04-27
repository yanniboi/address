<?php

/**
 * @file
 * Contains Drupal\address\Form\AddressFormatForm.
 */

namespace Drupal\address\Form;

use CommerceGuys\Addressing\Enum\AddressField;
use CommerceGuys\Addressing\Enum\AdministrativeAreaType;
use CommerceGuys\Addressing\Enum\DependentLocalityType;
use CommerceGuys\Addressing\Enum\LocalityType;
use CommerceGuys\Addressing\Enum\PostalCodeType;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Locale\CountryManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AddressFormatForm extends EntityForm {

  /**
   * The address format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The country manager.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManager;

  /**
   * Creates an AddressFormatForm instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The address format storage.
   */
  public function __construct(EntityStorageInterface $storage, CountryManagerInterface $countryManager) {
    $this->storage = $storage;
    $this->countryManager = $countryManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityManagerInterface $entityManager */
    $entityManager = $container->get('entity.manager');

    return new static($entityManager->getStorage('address_format'), $container->get('country_manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $formState) {
    $form = parent::form($form, $formState);
    $addressFormat = $this->entity;
    $fields = [
      AddressField::RECIPIENT => $this->t('Recipient'),
      AddressField::ORGANIZATION => $this->t('Organization'),
      AddressField::ADDRESS_LINE1 => $this->t('Address line 1'),
      AddressField::ADDRESS_LINE2 => $this->t('Address line 2'),
      AddressField::SORTING_CODE => $this->t('Sorting code'),
      AddressField::POSTAL_CODE => $this->t('Postal code'),
      AddressField::DEPENDENT_LOCALITY => $this->t('Dependent locality'),
      AddressField::LOCALITY => $this->t('Locality'),
      AddressField::ADMINISTRATIVE_AREA => $this->t('Administrative area'),
    ];

    $countryCode = $addressFormat->getCountryCode();
    if ($countryCode == 'ZZ') {
      $form['countryCode'] = array(
        '#type' => 'item',
        '#title' => $this->t('Country'),
        '#markup' => $this->t('Generic'),
      );
    }
    else {
      $form['countryCode'] = array(
        '#type' => 'select',
        '#title' => $this->t('Country'),
        '#default_value' => $addressFormat->getCountryCode(),
        '#required' => TRUE,
        '#options' => $this->countryManager->getList(),
        '#disabled' => !$addressFormat->isNew(),
      );
    }

    $form['format'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Format'),
      '#description' => $this->t('Available tokens: @tokens', array('@tokens' => implode(', ', AddressField::getTokens()))),
      '#default_value' => $addressFormat->getFormat(),
      '#required' => TRUE,
    );
    $form['requiredFields'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Required fields'),
      '#options' => $fields,
      '#default_value' => $addressFormat->getRequiredFields(),
    );
    $form['uppercaseFields'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Uppercase fields'),
      '#description' => t('Uppercased on envelopes to faciliate automatic post handling.'),
      '#options' => $fields,
      '#default_value' => $addressFormat->getUppercaseFields(),
    );
    $form['postalCodePattern'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Postal code pattern'),
      '#description' => $this->t('Regular expression used to validate postal codes.'),
      '#default_value' => $addressFormat->getPostalCodePattern(),
    );
    $form['postalCodePrefix'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Postal code prefix'),
      '#description' => $this->t('Added to postal codes when formatting an address for international mailing.'),
      '#default_value' => $addressFormat->getPostalCodePrefix(),
      '#size' => 5,
    );

    $form['postalCodeType'] = array(
      '#type' => 'select',
      '#title' => $this->t('Postal code type'),
      '#default_value' => $addressFormat->getPostalCodeType(),
      '#options' =>  [
        PostalCodeType::POSTAL => $this->t('Postal'),
        PostalCodeType::ZIP => $this->t('Zip'),
        PostalCodeType::PIN => $this->t('Pin'),
      ],
      '#empty_value' => '',
    );
    $form['dependentLocalityType'] = array(
      '#type' => 'select',
      '#title' => $this->t('Dependent locality type'),
      '#default_value' => $addressFormat->getDependentLocalityType(),
      '#options' => [
        DependentLocalityType::DISTRICT => $this->t('District'),
        DependentLocalityType::NEIGHBORHOOD => $this->t('Neighborhood'),
        DependentLocalityType::VILLAGE_TOWNSHIP => $this->t('Village township'),
        DependentLocalityType::SUBURB => $this->t('Suburb'),
      ],
      '#empty_value' => '',
    );
    $form['localityType'] = array(
      '#type' => 'select',
      '#title' => $this->t('Locality type'),
      '#default_value' => $addressFormat->getLocalityType(),
      '#options' => [
        LocalityType::CITY => t('City'),
        LocalityType::DISTRICT => t('District'),
        LocalityType::POST_TOWN => t('Post town'),
      ],
      '#empty_value' => '',
    );
    $form['administrativeAreaType'] = array(
      '#type' => 'select',
      '#title' => $this->t('Administrative area type'),
      '#default_value' => $addressFormat->getAdministrativeAreaType(),
      '#options' => [
        AdministrativeAreaType::AREA => $this->t('Area'),
        AdministrativeAreaType::COUNTY => $this->t('County'),
        AdministrativeAreaType::DEPARTMENT => $this->t('Department'),
        AdministrativeAreaType::DISTRICT => $this->t('District'),
        AdministrativeAreaType::DO_SI => $this->t('Do si'),
        AdministrativeAreaType::EMIRATE => $this->t('Emirate'),
        AdministrativeAreaType::ISLAND => $this->t('Island'),
        AdministrativeAreaType::OBLAST => $this->t('Oblast'),
        AdministrativeAreaType::PARISH => $this->t('Parish'),
        AdministrativeAreaType::PREFECTURE => $this->t('Prefecture'),
        AdministrativeAreaType::PROVINCE => $this->t('Province'),
        AdministrativeAreaType::STATE => $this->t('State'),
      ],
      '#empty_value' => '',
    );

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
        $formState->setErrorByName($requiredField, $this->t('%title is required.', array('%title' => $title)));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $formState) {
    $addressFormat = $this->entity;
    $addressFormat->save();
    drupal_set_message($this->t('Saved the %label address format.', array(
      '%label' => $addressFormat->label(),
    )));
    $formState->setRedirectUrl($addressFormat->urlInfo('collection'));
  }

}
