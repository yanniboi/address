<?php

/**
 * @file
 * Contains \Drupal\address\ZoneMemberPluginCollection.
 */

namespace Drupal\address;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A collection of zone members.
 */
class ZoneMemberPluginCollection extends DefaultLazyPluginCollection {

  /**
   * The parent zone.
   *
   * @var \Drupal\address\ZoneInterface
   */
  protected $parentZone;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\address\ZoneInterface $parentZone
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

}
