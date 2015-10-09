<?php

/**
 * @file
 * Contains \Drupal\address\Plugin\views\field\Subdivision.
 */

namespace Drupal\address\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Displays the subdivision name instead of the id.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("subdivision")
 */
class Subdivision extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    if (empty($value)) {
      return '';
    }

    $entity = $this->getEntity($values);
    $address = $entity->{$this->definition['field_name']}->first();
    switch ($this->definition['property']) {
      case 'administrative_area':
        $parentId = NULL;
        $needsParent = FALSE;
        break;
      case 'locality':
        $parentId = $address->administrative_area;
        $needsParent = TRUE;
        break;
      case 'dependent_locality':
        $parentId = $address->locality;
        $needsParent = TRUE;
        break;
    }

    $subdivisionRepository = \Drupal::service('address.subdivision_repository');
    if (!$needsParent || !empty($parentId)) {
      $subdivisions = $subdivisionRepository->getList($address->country_code, $parentId);
      if ($subdivisions[$value]) {
        $value = $subdivisions[$value];
      }
    }

    return $this->sanitizeValue($value);
  }
}
