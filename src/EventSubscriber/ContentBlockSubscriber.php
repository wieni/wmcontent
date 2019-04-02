<?php

namespace Drupal\wmcontent\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\wmcontent\Event\ContentBlockChangedEvent;
use Drupal\wmcontent\WmContentManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContentBlockSubscriber implements EventSubscriberInterface
{
    /** @var WmContentManager */
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

        if (!$host instanceof EntityInterface) {
            return;
        }

        $cid = sprintf('%s:%s', $host->getEntityTypeId(), $host->id());

        if (array_key_exists($cid, $this->updatedHosts)) {
            return;
        }

        $host->set('changed', time());
        $host->save();
        $this->updatedHosts[$cid] = true;
    }
}
