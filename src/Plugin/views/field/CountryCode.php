<?php

/**
 * @file
 * Contains \Drupal\address\Plugin\views\field\CountryCode.
 */

namespace Drupal\address\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Allows the country name to be displayed instead of the country code.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("country_code")
 */
class CountryCode extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['display_name'] = array('default' => TRUE);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['display_name'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Display the localized country name instead of the two character country code'),
      '#default_value' => !empty($this->options['display_name']),
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    if (!empty($this->options['display_name'])) {
      $countries = \Drupal::service('address.country_repository')->getList();
      $value = $countries[$value];
    }

    return $this->sanitizeValue($value);
  }

}
