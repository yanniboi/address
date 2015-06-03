<?php

/**
 * @file
 * Contains \Drupal\address\SubdivisionRepository.
 */

namespace Drupal\address;

use CommerceGuys\Addressing\Repository\SubdivisionRepository as ExternalSubdivisionRepository;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageManagerInterface;

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
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Creates a SubdivisionRepository instance.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(CacheBackendInterface $cache, LanguageManagerInterface $languageManager) {
    $this->cache = $cache;
    $this->languageManager = $languageManager;

    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public function getDepth($countryCode) {
    if (empty($this->depths)) {
      $cacheKey = 'address.subdivisions.depths';
      if ($cached = $this->cache->get($cacheKey)) {
        $this->depths = $cached->data;
      }
      else {
        $filename = $this->definitionPath . 'depths.json';
        $this->depths = json_decode(file_get_contents($filename), true);
        $this->cache->set($cacheKey, $this->depths, CacheBackendInterface::CACHE_PERMANENT, ['subdivisions']);
      }
    }

    return isset($this->depths[$countryCode]) ? $this->depths[$countryCode] : 0;
  }

  /**
   * {@inheritdoc}
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

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLocale() {
    return $this->languageManager->getConfigOverrideLanguage()->getId();
  }

}
