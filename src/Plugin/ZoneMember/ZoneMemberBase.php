<?php

/**
 * @file
 * Contains \Drupal\address\Plugin\ZoneMember\ZoneMemberBase.
 */

namespace Drupal\address\Plugin\ZoneMember;

use CommerceGuys\Addressing\Model\AddressInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;

/**
 * Defines a base zone member class.
 */
abstract class ZoneMemberBase extends PluginBase implements ZoneMemberInterface {

  /**
   * The parent zone.
   *
   * @var \Drupal\address\Entity\ZoneInterface
   */
  protected $parentZone;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);

    $this->setConfiguration($configuration);
    if (isset($pluginDefinition['parent_zone'])) {
      $this->parentZone = $pluginDefinition['parent_zone'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'id' => '',
      'name' => '',
      'weight' => 0,
      'plugin' => $this->pluginDefinition['id'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $formState) {
    $form['#member'] = $this;
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $this->configuration['name'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $formState) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $formState) {
    if (!$formState->getErrors()) {
      $this->configuration['name'] = $formState->getValue('name');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->configuration['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->configuration['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->configuration['weight'];
  }

  /**
   * {@inheritdoc}
   */
  public function getParentZone() {
    return $this->parentZone;
  }

  /**
   * {@inheritdoc}
   */
  abstract public function match(AddressInterface $address);

}
