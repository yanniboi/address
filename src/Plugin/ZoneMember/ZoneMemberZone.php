<?php

/**
 * @file
 * Contains \Drupal\address\Plugin\ZoneMember\ZoneMemberZone.
 */

namespace Drupal\address\Plugin\ZoneMember;

use CommerceGuys\Addressing\Model\AddressInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Matches a single zone.
 *
 * @ZoneMember(
 *   id = "zone",
 *   name = @Translation("Zone"),
 * )
 */
class ZoneMemberZone extends ZoneMemberBase implements ContainerFactoryPluginInterface {

  /**
   * The zone storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $zoneStorage;

  /**
   * Constructs a new ZoneMemberZone object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $pluginId
   *   The pluginId for the plugin instance.
   * @param mixed $pluginDefinition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager service.
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, EntityManagerInterface $entityManager) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);

    $this->zoneStorage = $entityManager->getStorage('zone');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'zone' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $formState) {
    $form = parent::buildConfigurationForm($form, $formState);
    $form['zone'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Zone'),
      '#default_value' => $this->zoneStorage->load($this->configuration['zone']),
      '#target_type' => 'zone',
      '#tags' => FALSE,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $formState) {
    parent::submitConfigurationForm($form, $formState);

    if (!$formState->getErrors()) {
      $this->configuration['zone'] = $formState->getValue('zone');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function match(AddressInterface $address) {
    $zone = $this->zoneStorage->load($this->configuration['zone']);
    if ($zone) {
      return $zone->match($address);
    }
  }

}
