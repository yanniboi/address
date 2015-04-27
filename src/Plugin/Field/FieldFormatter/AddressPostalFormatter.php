<?php

/**
 * @file
 * Contains \Drupal\address\Plugin\field\formatter\AddressPostalFormatter.
 */

namespace Drupal\address\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'address_postal' formatter.
 *
 * @FieldFormatter(
 *   id = "address_postal",
 *   label = @Translation("Postal Address"),
 *   field_types = {
 *     "address",
 *   },
 * )
 */
class AddressPostalFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'originCountry' => \Drupal::config('system.date')->get('country.default'),
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $formState) {
    $elements['originCountry'] = array(
      '#title' => t('Origin Country'),
      '#description' => t('The Origin Country to format the address in.'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('originCountry'),
      '#size' => 2,
      '#required' => TRUE,
    );
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $settings = $this->getSettings();
    $elements = array();
    foreach ($items as $delta => $item) {
      $elements[$delta] = array('#markup' => '');
    }
    return $elements;
  }

}
