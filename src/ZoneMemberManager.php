<?php

/**
 * @file
 * Contains \Drupal\address\ZoneMemberManager.
 */

namespace Drupal\address;

use Drupal\address\Entity\ZoneInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages zone member plugins.
 */
class ZoneMemberManager extends DefaultPluginManager {

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * Constructs a new ZoneMemberManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Component\Uuid\UuidInterface $uuidService
   *   The uuid service.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cacheBackend, ModuleHandlerInterface $moduleHandler, UuidInterface $uuidService) {
    parent::__construct('Plugin/ZoneMember', $namespaces, $moduleHandler, 'Drupal\address\Plugin\ZoneMember\ZoneMemberInterface', 'Drupal\address\Annotation\ZoneMember');

    $this->alterInfo('zone_member_info');
    $this->setCacheBackend($cacheBackend, 'zone_member_plugins');
    $this->uuidService = $uuidService;
  }

  /**
   * {@inheritdoc}
   *
   * Passes the $parentZone along to the instantiated plugin.
   */
  public function createInstance($pluginId, array $configuration = [], ZoneInterface $parentZone = NULL) {
    $pluginDefinition = $this->getDefinition($pluginId);
    $pluginDefinition['parent_zone'] = $parentZone;
    $plugin_class = DefaultFactory::getPluginClass($pluginId, $pluginDefinition);
    // Generate an id for the plugin instance, if it wasn't provided.
    if (empty($configuration['id'])) {
      $configuration['id'] = $this->uuidService->generate();
    }
    // If the plugin provides a factory method, pass the container to it.
    if (is_subclass_of($plugin_class, 'Drupal\Core\Plugin\ContainerFactoryPluginInterface')) {
      $plugin = $plugin_class::create(\Drupal::getContainer(), $configuration, $pluginId, $pluginDefinition, $parentZone);
    }
    else {
      $plugin = new $plugin_class($configuration, $pluginId, $pluginDefinition, $parentZone);
    }

    return $plugin;
  }

}
