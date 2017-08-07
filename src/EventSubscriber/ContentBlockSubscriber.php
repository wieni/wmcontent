<?php

namespace Drupal\wmcontent\EventSubscriber;

use Drupal\wmcontent\Event\ContentBlockChangedEvent;
use Drupal\wmcontent\WmContentManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContentBlockSubscriber implements EventSubscriberInterface
{
    /** @var \Drupal\wmcontent\WmContentManager */
    private $manager;

    private $updatedHosts = [];

    public function __construct(WmContentManager $manager)
    {
        $this->manager = $manager;
    }

    public static function getSubscribedEvents()
    {
        $events[ContentBlockChangedEvent::NAME][] = ['updateHostEntity'];
        return $events;
    }

    /**
     * Trigger an update of the contentblock's host entity
     */
    public function updateHostEntity(ContentBlockChangedEvent $event)
    {
        $host = $this->manager->getHost($event->getContentBlock());
        $cid = sprintf('%s:%s', $host->getEntityTypeId(), $host->id());
        if ($host && !array_key_exists($cid, $this->updatedHosts)) {
            $host->save();
            $this->updatedHosts[$cid] = true;
        }
    }
}
