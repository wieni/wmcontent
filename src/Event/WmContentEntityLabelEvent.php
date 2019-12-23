<?php

namespace Drupal\wmcontent\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\field\FieldConfigInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * The Event that fires when looking for entity labels on wmcontent tabs
 */
class WmContentEntityLabelEvent extends Event
{
    public const NAME = 'wmcontent.entity.label';

    /** @var EntityInterface */
    protected $entity;
    /** @var string */
    protected $fieldName;
    /** @var FieldConfigInterface */
    protected $fieldConfig;
    /** @var string|null */
    protected $label;

    public function __construct(
        EntityInterface $entity,
        string $fieldName,
        FieldConfigInterface $fieldConfig
    ) {
        $this->entity = $entity;
        $this->fieldName = $fieldName;
        $this->fieldConfig = $fieldConfig;
    }

    public function setEntity(EntityInterface $entity): void
    {
        $this->entity = $entity;
    }

    public function setFieldName(string $fieldName): void
    {
        $this->fieldName = $fieldName;
    }

    public function getEntity(): EntityInterface
    {
        return $this->entity;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getFieldConfig(): FieldConfigInterface
    {
        return $this->fieldConfig;
    }

    public function setFieldConfig(FieldConfigInterface $fieldConfig): void
    {
        $this->fieldConfig = $fieldConfig;
    }
}
