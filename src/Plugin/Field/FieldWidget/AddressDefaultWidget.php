<?php

/**
 * @file
 * Contains \Drupal\address\Plugin\Field\FieldWidget\AddressDefaultWidget.
 */

namespace Drupal\address\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'address' widget.
 *
 * @FieldWidget(
 *   id = "address_default",
 *   label = @Translation("Address"),
 *   field_types = {
 *     "address"
 *   },
 * )
 */
class AddressDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element += array(
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#open' => TRUE,
    );

    $element['country_code'] = array(
      '#type' => 'textfield',
      '#title' => t('Country'),
      '#default_value' => $items[$delta]->country_code,
    );
    $element['recipient'] = array(
      '#type' => 'textfield',
      '#title' => t('Recipient'),
      '#default_value' => $items[$delta]->recipient,
    );
    $element['organization'] = array(
      '#type' => 'textfield',
      '#title' => t('Organization'),
      '#default_value' => $items[$delta]->organization,
    );
    $element['address_line1'] = array(
      '#type' => 'textfield',
      '#title' => t('Address'),
      '#default_value' => $items[$delta]->address_line1,
    );
    $element['address_line2'] = array(
      '#type' => 'textfield',
      '#title' => t('Address line 2'),
      '#title_display' => 'invisible',
      '#default_value' => $items[$delta]->address_line2,
    );
    $element['locality'] = array(
      '#type' => 'textfield',
      '#title' => t('Locality'),
      '#default_value' => $items[$delta]->locality,
    );
    $element['dependent_locality'] = array(
      '#type' => 'textfield',
      '#title' => t('Dependant locality'),
      '#default_value' => $items[$delta]->dependent_locality,
    );
    $element['postal_code'] = array(
      '#type' => 'textfield',
      '#title' => t('Postal code'),
      '#default_value' => $items[$delta]->postal_code,
    );
    $element['sorting_code'] = array(
      '#type' => 'textfield',
      '#title' => t('Sorting code'),
      '#default_value' => $items[$delta]->sorting_code,
    );
    $element['administrative_area'] = array(
      '#type' => 'textfield',
      '#title' => t('Administrative area'),
      '#default_value' => $items[$delta]->administrative_area,
    );

    return $element;
  }
}
