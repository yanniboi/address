<?php

/**
 * @file
 * Contains \Drupal\address\Form\AddressFormatImporterForm.
 */

namespace Drupal\address\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Locale\CountryManager;
use Drupal\address\AddressFieldImporter;


/**
 * Builds the form to import an adress format.
 */
class AddressFormatImporterForm extends FormBase {

  /**
   * The address format importer.
   *
   * @var \Drupal\address\AddressFormatImporterInterface
   */
  protected $addressFormatImporter;

  /**
   * Constructs a new AddressFormatImporterForm.
   */
  public function __construct() {
    $this->addressFormatImporter = \Drupal::service('address.address_format_importer');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $formState) {
    $addressFormats = $this->addressFormatImporter->getImportableAddressFormats();

    if (!$addressFormats) {
      $form['message'] = array(
        '#markup' => $this->t('All address formats are already imported.'),
      );
    }
    else {
      $form['country_code'] = array(
        '#type' => 'select',
        '#title' => $this->t('Country'),
        '#description' => $this->t('Please select the country you would like to import.'),
        '#required' => TRUE,
        '#options' => CountryManager::getStandardList(),
      );

      $form['actions']['#type'] = 'actions';
      $form['actions']['import'] = array(
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#name' => 'import',
        '#value' => $this->t('Import'),
        '#submit' => array('::submitForm'),
      );
      $form['actions']['import_new'] = array(
        '#type' => 'submit',
        '#name' => 'import_and_new',
        '#value' => $this->t('Import and new'),
        '#submit' => array('::submitForm'),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {
    $values = $formState->getValues();
    $addressFormat = $this->addressFormatImporter->importAddressFormat(
      $values['country_code']
    );

    try {
      $addressFormat->save();
      drupal_set_message(
        $this->t('Imported the %label address format.', array('%label' => $addressFormat->label()))
      );
      $triggeringElement = $formState->getTriggeringElement();
      if ($triggeringElement['#name'] == 'import_and_new') {
        $formState->setRebuild();
      }
      else {
        $formState->setRedirect('entity.address_format.list');
      }
    } catch (\Exception $e) {
      drupal_set_message($this->t('The %label address format was not imported.', array('%label' => $addressFormat->label())), 'error');
      $this->logger('address')->error($e);
      $formState->setRebuild();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'address_address_format_importer';
  }
}
