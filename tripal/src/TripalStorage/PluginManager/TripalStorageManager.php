<?php

namespace Drupal\tripal\TripalStorage\PluginManager;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides a tripal storage plugin manager.
 */
class TripalStorageManager extends DefaultPluginManager {

  /**
   * Constructs a new tripal storage manager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param string $plugin_interface
   *   The interface each plugin should implement.
   * @param string $plugin_definition_annotation_name
   *   The name of the annotation that contains the plugin definition.
   */
  public function __construct(
      \Traversable $namespaces
      ,CacheBackendInterface $cache_backend
      ,ModuleHandlerInterface $module_handler
  ) {
    parent::__construct(
        "Plugin/TripalStorage"
        ,$namespaces
        ,$module_handler
        ,'Drupal\tripal\TripalStorage\Interface\TripalStorageInterface'
        ,'Drupal\tripal\TripalStorage\Annotation\TripalStorage'
    );
    $this->alterInfo("tripal_storage_info");
    $this->setCacheBackend($cache_backend,"tripal_storage_plugins");
  }
}
