<?php

/**
 * @file
 * Contains \Drupal\address\Plugin\views\field\Subdivision.
 */

namespace Drupal\address\Plugin\views\field;

use CommerceGuys\Addressing\Repository\SubdivisionRepositoryInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays the subdivision name instead of the id.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("subdivision")
 */
class Subdivision extends FieldPluginBase {

  /**
   * The subdivision repository.
   *
   * @var \CommerceGuys\Addressing\Repository\SubdivisionRepositoryInterface
   */
  protected $subdivisionRepository;

  /**
   * Constructs a Subdivision object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $pluginId
   *   The id of the plugin instance.
   * @param mixed $pluginDefinition
   *   The plugin implementation definition.
   * @param \CommerceGuys\Addressing\Repository\SubdivisionRepositoryInterface $subdivisionRepository
   *   The subdivision repository.
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, SubdivisionRepositoryInterface $subdivisionRepository) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);

    $this->subdivisionRepository = $subdivisionRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('address.subdivision_repository')
    );
  }

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

    if (!$needsParent || !empty($parentId)) {
      $subdivisions = $this->subdivisionRepository->getList($address->country_code, $parentId);
      if (isset($subdivisions[$value])) {
        $value = $subdivisions[$value];
      }
    }

    return $this->sanitizeValue($value);
  }
}
