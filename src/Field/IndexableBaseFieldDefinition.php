<?php

namespace Drupal\wmcontent\Field;

use Drupal\Core\Field\BaseFieldDefinition;

class IndexableBaseFieldDefinition extends BaseFieldDefinition
{
    public function addIndex(string $propertyName, int $length = null, string $key = null): self
    {
        unset($this->schema);

        $value = ($length === null)
            ? $propertyName
            : [$propertyName, $length];

        if ($key === null) {
            $this->indexes[][] = $value;
        } else {
            $this->indexes[$key][] = $value;
        }

        return $this;
    }

    public static function wrap(BaseFieldDefinition $definition): self
    {
        $oldDefinition = clone $definition;

        $newDefinition = new static([]);
        $newDefinition->type = $oldDefinition->type;
        $newDefinition->schema = $oldDefinition->schema;
        $newDefinition->itemDefinition = $oldDefinition->itemDefinition;
        $newDefinition->definition = $oldDefinition->definition;

        return $newDefinition;
    }
}
