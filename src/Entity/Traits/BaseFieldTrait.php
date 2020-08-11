<?php

namespace Drupal\wmcontent\Entity\Traits;

use Drupal\Core\Field\BaseFieldDefinition;

trait BaseFieldTrait {

    public static function getBooleanBaseFieldDefinition(bool $required): BaseFieldDefinition
    {
        return BaseFieldDefinition::create('boolean')
            ->setCardinality(1)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
            ])
            ->setRequired($required)
            ->setSetting('unsigned', TRUE);
    }

    public static function getDecimalBaseFieldDefinition(bool $required): BaseFieldDefinition
    {
        return BaseFieldDefinition::create('decimal')
            ->setCardinality(1)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayOptions('form', [
                'type' => 'number',
            ])
            ->setRequired($required)
            ->setSettings([
                'precision' => 10,
                'scale' => 2,
            ])
            ->setStorageRequired(false);
    }

    public static function getEntityReferenceBaseFieldDefinition(bool $required, string $targetType, string $targetBundle = null): BaseFieldDefinition
    {
        return BaseFieldDefinition::create('entity_reference')
            ->setCardinality(1)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
            ])
            ->setRequired($required)
            ->setSettings([
                'target_type' => $targetType,
                'handler_settings' => [
                    'target_bundles' => [$targetBundle],
                ],
            ]);
    }

    public static function getIntegerBaseFieldDefinition(bool $required): BaseFieldDefinition
    {
        return BaseFieldDefinition::create('integer')
            ->setCardinality(1)
            ->setSetting('unsigned', TRUE)
            ->setRequired($required)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayOptions('form', [
                'type' => 'number',
            ]);
    }

    public static function getStringBaseFieldDefinition(bool $required): BaseFieldDefinition
    {
        return BaseFieldDefinition::create('string')
            ->setCardinality(1)
            ->setSettings([
                'max_length' => 255,
                'text_processing' => 0,
            ])
            ->setRequired($required)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
            ]);
    }
}
