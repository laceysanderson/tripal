<?php

namespace Drupal\tripal4\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the tripal vocabulary plugin manager.
 */
class ImporterManager extends DefaultPluginManager {

  /**
   * Constructs a new tripal vocabulary plugin manager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(
      \Traversable $namespaces
      ,CacheBackendInterface $cache_backend
      ,ModuleHandlerInterface $module_handler
  ) {
    parent::__construct(
        "Plugin/TripalVocab"
        ,$namespaces
        ,$module_handler
        ,'Drupal\tripal4\Plugin\TripalVocabInterface'
        ,'Drupal\tripal4\Annotation\TripalVocab'
    );
    $this->alterInfo('tripal_vocab_info');
    $this->setCacheBackend($cache_backend,'tripal_vocab_plugins');
  }

}
