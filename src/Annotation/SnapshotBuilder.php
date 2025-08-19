<?php

namespace Drupal\wmcontent\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * @Annotation
 */
class SnapshotBuilder extends Plugin
{
    /** @var string */
    public $entity_type;
    /** @var string */
    public $bundle;

    public function getId()
    {
        foreach (['entity_type', 'bundle'] as $param) {
            if (empty($this->definition[$param])) {
                throw new \RuntimeException(sprintf(
                    'The "%s" annotation parameter is required in %s',
                    $param,
                    static::class
                ));
            }
        }

        return implode('.', [
            $this->definition['entity_type'],
            $this->definition['bundle'],
        ]);
    }
}
