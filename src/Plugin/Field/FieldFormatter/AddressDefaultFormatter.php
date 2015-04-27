<?php

/**
 * @file
 * Contains \Drupal\address\Plugin\field\formatter\AddressDefaultFormatter.
 */

namespace Drupal\address\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'address_default' formatter.
 *
 * @FieldFormatter(
 *   id = "address_default",
 *   label = @Translation("Basic Address"),
 *   field_types = {
 *     "address",
 *   },
 * )
 */
class AddressDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();
    foreach ($items as $delta => $item) {
      $elements[$delta]['#type'] = 'fieldset';
      $elements[$delta]['country_code'] = array(
        '#type' => 'item',
        '#title' => t('Country Code'),
        '#markup' => $items[$delta]->country_code,
      );
      $elements[$delta]['administrative_area'] = array(
        '#type' => 'item',
        '#title' => t('Administrative Area'),
        '#markup' => $items[$delta]->administrative_area,
      );
      $elements[$delta]['locality'] = array(
        '#type' => 'item',
        '#title' => t('Locality'),
        '#markup' => $items[$delta]->locality,
      );
      $elements[$delta]['dependent_locality'] = array(
        '#type' => 'item',
        '#title' => t('Dependant Locality'),
        '#markup' => $items[$delta]->dependent_locality,
      );
      $elements[$delta]['postal_code'] = array(
        '#type' => 'item',
        '#title' => t('Postal Code'),
        '#markup' => $items[$delta]->postal_code,
      );
      $elements[$delta]['sorting_code'] = array(
        '#type' => 'item',
        '#title' => t('Sorting Code'),
        '#markup' => $items[$delta]->sorting_code,
      );
      $elements[$delta]['address_line1'] = array(
        '#type' => 'item',
        '#title' => t('Address Line 1'),
        '#markup' => $items[$delta]->address_line1,
      );
      $elements[$delta]['address_line2'] = array(
        '#type' => 'item',
        '#title' => t('Address Line 2'),
        '#markup' => $items[$delta]->address_line2,
      );
      $elements[$delta]['organization'] = array(
        '#type' => 'item',
        '#title' => t('Organization'),
        '#markup' => $items[$delta]->organization,
      );
      $elements[$delta]['recipient'] = array(
        '#type' => 'item',
        '#title' => t('Recipient'),
        '#markup' => $items[$delta]->recipient,
      );
    }
    return $elements;
  }

}
