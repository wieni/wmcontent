<?php

namespace Drupal\wmcontent\Service\Snapshot;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\wmcontent\Annotation\SnapshotBuilder;
use Drupal\wmcontent\Attribute\SnapshotBuilder as SnapshotBuilderAttribute;

class SnapshotPluginManager extends DefaultPluginManager
{
    public function __construct(
        \Traversable $namespaces,
        CacheBackendInterface $cacheBackend,
        ModuleHandlerInterface $moduleHandler
    ) {
        // Attribute discovery (and this constructor signature) only exists
        // since Drupal 10.2.
        if (class_exists(Plugin::class)) {
            parent::__construct(
                'SnapshotBuilder',
                $namespaces,
                $moduleHandler,
                SnapshotBuilderBase::class,
                SnapshotBuilderAttribute::class,
                SnapshotBuilder::class
            );
        } else {
            parent::__construct(
                'SnapshotBuilder',
                $namespaces,
                $moduleHandler,
                SnapshotBuilderBase::class,
                SnapshotBuilder::class
            );
        }
        $this->alterInfo('wmcontent_snapshot_builders');
        $this->setCacheBackend($cacheBackend, 'wmcontent_snapshotbuilder_info_plugins');
    }
}
