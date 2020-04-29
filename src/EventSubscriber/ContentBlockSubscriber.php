<?php

namespace Drupal\wmcontent\EventSubscriber;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\wmcontent\Event\ContentBlockChangedEvent;
use Drupal\wmcontent\WmContentManager;
use Drupal\wmcontent\WmContentManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContentBlockSubscriber implements EventSubscriberInterface
{
    /** @var WmContentManager */
    protected $manager;
    /** @var array */
    protected $updatedHosts = [];

    public function __construct(
        WmContentManagerInterface $manager
    ) {
        $this->manager = $manager;
    }

    public static function getSubscribedEvents()
    {
        $events[ContentBlockChangedEvent::NAME][] = ['updateHostEntity'];

        return $events;
    }

    /** Trigger an update of the contentblock's host entity */
    public function updateHostEntity(ContentBlockChangedEvent $event)
    {
        $host = $this->manager->getHost($event->getContentBlock());

        if (!$host instanceof ContentEntityInterface) {
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
