<?php

namespace Drupal\wmcontent\Service\Snapshot;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\wmcontent\Annotation\SnapshotBuilder;

class SnapshotPluginManager extends DefaultPluginManager
{
    public function __construct(
        \Traversable $namespaces,
        CacheBackendInterface $cacheBackend,
        ModuleHandlerInterface $moduleHandler
    ) {
        parent::__construct(
            'SnapshotBuilder',
            $namespaces,
            $moduleHandler,
            SnapshotBuilderBase::class,
            SnapshotBuilder::class
        );
        $this->alterInfo('wmcontent_snapshot_builders');
        $this->setCacheBackend($cacheBackend, 'wmcontent_snapshotbuilder_info_plugins');
    }
}
