<?php

namespace Drupal\wmcontent\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Entity\Entity;

/**
 * The Event that fires when looking for entity labels on wmcontent tabs
 */
class WmContentEntityLabelEvent extends Event
{
    const NAME = 'wmcontent.entity.label';

    protected $entity;
    protected $fieldName;
    protected $label;
    protected $fieldSettings;

    /**
     * Constructor.
     */
    public function __construct(Entity $entity, $fieldName, array $fieldSettings)
    {
        $this->entity = $entity;
        $this->fieldName = $fieldName;
        $this->fieldSettings = $fieldSettings;
        $this->label = '';
    }

    /**
     * Setter for the entity.
     *
     * @param Entity $entity
     *   Current entity.
     */
    public function setEntity(Entity $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Setter for the fieldname.
     *
     * @param string $fieldName
     *   Current fieldname.
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
    }

    /**
     * Getter for the entity.
     *
     * @return entity
     *   Current entity.
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Getter for the fieldname.
     *
     * @return string
     *  Current fieldname.
     * */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * Get the label.
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set the label.
     *
     * @param string $label
     *   New label.
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * Get the field settings
     *
     * @return array
     */
    public function getFieldSettings()
    {
        return $this->fieldSettings;
    }

    /**
     * Set the field settings
     *
     * @param array $fieldSettings
     */
    public function setFieldSettings(array $fieldSettings)
    {
        $this->fieldSettings = $fieldSettings;
    }
}
