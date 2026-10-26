<?php

namespace Drupal\wmcontent\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;

/**
 * Attribute counterpart of the SnapshotBuilder annotation.
 *
 * Takes the same properties. The plugin ID is derived from them in the same
 * way: "<entity_type>.<bundle>".
 *
 * @see \Drupal\wmcontent\Annotation\SnapshotBuilder
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class SnapshotBuilder extends Plugin
{
    public function __construct(
        public readonly string $entity_type,
        public readonly string $bundle,
        ?string $deriver = null,
    ) {
        parent::__construct(implode('.', [$entity_type, $bundle]), $deriver);
    }
}
