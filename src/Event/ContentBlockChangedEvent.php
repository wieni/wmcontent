<?php

namespace Drupal\wmcontent\Event;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that fires when a content block has been created/updated
 */
class ContentBlockChangedEvent extends Event
{
    const NAME = 'wmcontent.contentblock.changed';
    /** @var \Drupal\Core\Entity\ContentEntityInterface */
    private $contentBlock;
    /** @var \Drupal\wmcontent\Entity\WmContentContainer[] */
    private $containers;

    public function __construct(EntityInterface $contentBlock, array $containers)
    {
        $this->contentBlock = $contentBlock;
        $this->containers = $containers;
    }

    /** @return \Drupal\Core\Entity\ContentEntityInterface */
    public function getContentBlock()
    {
        return $this->contentBlock;
    }

    /**
     * @return \Drupal\wmcontent\Entity\WmContentContainer[]
     */
    public function getContainers()
    {
        return $this->containers;
    }
}
