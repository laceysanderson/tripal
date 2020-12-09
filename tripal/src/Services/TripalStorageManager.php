<?php
namespace Drupal\tripal\Services;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides an tripalStorage plugin manager.
 */
class TripalStorageManager extends DefaultPluginManager {

  /**
   * Constructs a tripalStorageManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/TripalStorage',
      $namespaces,
      $module_handler,
      'Drupal\\tripal\\Plugin\\TripalStorage\\TripalStorageInterface',
      'Drupal\\tripal\\Plugin\\Annotation\\TripalStorage'
    );
    $this->alterInfo('tripalstorage_info');
    $this->setCacheBackend($cache_backend, 'tripalstorage_info_plugins');
  }

}
