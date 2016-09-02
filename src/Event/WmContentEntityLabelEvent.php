<?php

namespace Drupal\wmcontent\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Entity\Entity;

/**
 * The Event that fires when looking for entity labels on wmcontent tabs
 */
class WmContentEntityLabelEvent extends Event
{

    protected $entity;
    protected $label;

    /**
     * Constructor.
     */
    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
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
}
