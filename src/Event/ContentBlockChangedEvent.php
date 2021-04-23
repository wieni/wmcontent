<?php

namespace Drupal\wmcontent\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\wmcontent\WmContentContainerInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that fires when a content block has been created/updated/deleted
 */
class ContentBlockChangedEvent extends Event
{
    public const NAME = 'wmcontent.contentblock.changed';

    /** @var EntityInterface */
    protected $contentBlock;
    /** @var WmContentContainerInterface[] */
    protected $containers;

    public function __construct(EntityInterface $contentBlock, array $containers)
    {
        $this->contentBlock = $contentBlock;
        $this->containers = $containers;
    }

    public function getContentBlock(): EntityInterface
    {
        return $this->contentBlock;
    }

    public function getContainers(): array
    {
        return $this->containers;
    }
}
