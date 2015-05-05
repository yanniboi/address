<?php

/**
 * @file
 * Contains \Drupal\address\SubdivisionRepository.
 */

namespace Drupal\address;

use CommerceGuys\Addressing\Repository\SubdivisionRepository as ExternalSubdivisionRepository;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Defines the subdivision repository.
 *
 * Subdivisions are stored on disk in JSON and cached inside Drupal.
 */
class SubdivisionRepository extends ExternalSubdivisionRepository {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Creates a SubdivisionRepository instance.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(CacheBackendInterface $cache) {
    $this->cache = $cache;
    parent::__construct();
  }

  /**
   * Loads the subdivision definitions for the provided country code.
   *
   * @param string $countryCode
   *   The country code.
   * @param int $parentId
   *   The parent id.
   *
   * @return array
   *   The subdivision definitions.
   */
  protected function loadDefinitions($countryCode, $parentId = null) {
    // Treat the country code as the parent id on the top level.
    $parentId = $parentId ?: $countryCode;
    if (isset($this->definitions[$parentId])) {
      return $this->definitions[$parentId];
    }

    $cacheKey = 'address.subdivisions.' . $parentId;
    $filename = $this->definitionPath . $parentId . '.json';
    if ($cached = $this->cache->get($cacheKey)) {
      $this->definitions[$parentId] = $cached->data;
    }
    elseif ($rawDefinition = @file_get_contents($filename)) {
      $this->definitions[$parentId] = json_decode($rawDefinition, true);
      $this->cache->set($cacheKey, $this->definitions[$parentId], CacheBackendInterface::CACHE_PERMANENT, ['subdivisions']);
    }
    else {
      // Not found. Bypass further loading attempts.
      $this->definitions[$parentId] = [];
    }

    return $this->definitions[$parentId];
  }

}
