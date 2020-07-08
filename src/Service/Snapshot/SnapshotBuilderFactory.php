<?php

namespace Drupal\wmcontent\Service\Snapshot;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\DependencyInjection\ClassResolverInterface;

class SnapshotBuilderFactory
{
    /** @var \Drupal\wmcontent\Service\Snapshot\SnapshotPluginManager */
    protected $pluginManager;
    /** @var \Drupal\Core\DependencyInjection\ClassResolverInterface */
    protected $classResolver;

    public function __construct(
        SnapshotPluginManager $pluginManager,
        ClassResolverInterface $classResolver
    ) {
        $this->pluginManager = $pluginManager;
        $this->classResolver = $classResolver;
    }

    public function getSnapshotBuilder(string $entityTypeId, string $bundle): ?SnapshotBuilderBase
    {
        try {
            $id = implode('.', [$entityTypeId, $bundle]);
            $definition = $this->pluginManager->getDefinition($id);
        } catch (PluginNotFoundException $e) {
            return null;
        }

        $builder = $this->classResolver->getInstanceFromDefinition($definition['class']);
        if (!$builder instanceof SnapshotBuilderBase) {
            throw new \RuntimeException(sprintf(
                'Builder "%s" is not an instance of "%s" but "%s"',
                $definition['class'],
                SnapshotBuilderBase::class,
                get_class($builder)
            ));
        }

        return $builder;
    }
}
