<?php

/**
 * @file
 * Contains \Drupal\address\ZoneMemberPluginCollection.
 */

namespace Drupal\address;

use Drupal\address\Entity\ZoneInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * A collection of zone members.
 */
class ZoneMemberPluginCollection extends DefaultLazyPluginCollection {

  /**
   * {@inheritdoc}
   */
  protected $pluginKey = 'plugin';

  /**
   * The parent zone.
   *
   * @var \Drupal\address\Entity\ZoneInterface
   */
  protected $parentZone;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\address\Entity\ZoneInterface $parentZone
   *   The parent zone.
   */
  public function __construct(PluginManagerInterface $manager, array $configurations, ZoneInterface $parentZone) {
    parent::__construct($manager, $configurations);

    $this->parentZone = $parentZone;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instanceId) {
    $configuration = isset($this->configurations[$instanceId]) ? $this->configurations[$instanceId] : [];
    if (!isset($configuration[$this->pluginKey])) {
      throw new PluginNotFoundException($instanceId);
    }
    $this->set($instanceId, $this->manager->createInstance($configuration[$this->pluginKey], $configuration, $this->parentZone));
  }

  /**
   * {@inheritdoc}
   */
  public function sortHelper($aID, $bID) {
    $a_weight = $this->get($aID)->getWeight();
    $b_weight = $this->get($bID)->getWeight();
    if ($a_weight == $b_weight) {
      return 0;
    }

    return ($a_weight < $b_weight) ? -1 : 1;
  }

}
