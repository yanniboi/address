<?php

/**
 * @file
 * Contains \Drupal\address\CountryRepository.
 */

namespace Drupal\address;

use CommerceGuys\Intl\Country\CountryRepository as ExternalCountryRepository;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Locale\CountryManagerInterface;

/**
 * Defines the country repository.
 *
 * Countries are stored on disk in JSON and cached inside Drupal.
 */
class CountryRepository extends ExternalCountryRepository implements CountryManagerInterface {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Creates a CountryRepository instance.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(CacheBackendInterface $cache) {
    $this->cache = $cache;
    parent::__construct();
  }

  /**
   * Loads the country definitions for the provided locale.
   *
   * @param string $locale The desired locale.
   *
   * @return array
   */
  protected function loadDefinitions($locale) {
    if (isset($this->definitions[$locale])) {
      return $this->definitions[$locale];
    }

    $cacheKey = 'address.countries.' . $locale;
    if ($cached = $this->cache->get($cacheKey)) {
      $this->definitions[$locale] = $cached->data;
    }
    else {
      $filename = $this->definitionPath . $locale . '.json';
      $this->definitions[$locale] = json_decode(file_get_contents($filename), true);
      // Merge-in base definitions.
      $baseDefinitions = $this->loadBaseDefinitions();
      foreach ($this->definitions[$locale] as $countryCode => $definition) {
        $this->definitions[$locale][$countryCode] += $this->baseDefinitions[$countryCode];
      }
      $this->cache->set($cacheKey, $this->definitions[$locale], CacheBackendInterface::CACHE_PERMANENT, ['countries']);
    }

    return $this->definitions[$locale];
  }

  /**
   * Loads the base country definitions.
   *
   * @return array
   */
  protected function loadBaseDefinitions() {
    if (!empty($this->baseDefinitions)) {
      return $this->baseDefinitions;
    }

    $cacheKey = 'address.countries.base';
    if ($cached = $this->cache->get($cacheKey)) {
      $this->baseDefinitions = $cached->data;
    }
    else {
      $this->baseDefinitions = json_decode(file_get_contents($this->definitionPath . 'base.json'), true);
      $this->cache->set($cacheKey, $this->baseDefinitions, CacheBackendInterface::CACHE_PERMANENT, ['countries']);
    }

    return $this->baseDefinitions;
  }

}
