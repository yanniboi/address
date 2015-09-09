<?php

/**
 * @file
 * Contains \Drupal\address\Repository\SubdivisionRepository.
 */

namespace Drupal\address\Repository;

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
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
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
        $this->depths = json_decode(file_get_contents($filename), TRUE);
        $this->cache->set($cacheKey, $this->depths, CacheBackendInterface::CACHE_PERMANENT, ['subdivisions']);
      }
    }

    return isset($this->depths[$countryCode]) ? $this->depths[$countryCode] : 0;
  }

  /**
   * {@inheritdoc}
   */
  protected function loadDefinitions($countryCode, $parentId = NULL) {
    $lookupId = $parentId ?: $countryCode;
    if (isset($this->definitions[$lookupId])) {
      return $this->definitions[$lookupId];
    }

    // If there are predefined subdivisions at this level, try to load them.
    $this->definitions[$lookupId] = [];
    if ($this->hasData($countryCode, $parentId)) {
      $cacheKey = 'address.subdivisions.' . $lookupId;
      $filename = $this->definitionPath . $lookupId . '.json';
      if ($cached = $this->cache->get($cacheKey)) {
        $this->definitions[$lookupId] = $cached->data;
      }
      elseif ($rawDefinition = @file_get_contents($filename)) {
        $this->definitions[$lookupId] = json_decode($rawDefinition, TRUE);
        $this->cache->set($cacheKey, $this->definitions[$lookupId], CacheBackendInterface::CACHE_PERMANENT, ['subdivisions']);
      }
    }

    return $this->definitions[$lookupId];
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLocale() {
    return $this->languageManager->getConfigOverrideLanguage()->getId();
  }

}
