<?php

namespace Drupal\wmcontent\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\wmcontent\EntityUpdateService;
use Drupal\wmcontent\WmContentContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WmContentConfigSubscriber implements EventSubscriberInterface
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var EntityUpdateService */
    protected $entityUpdates;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        EntityUpdateService $entityUpdates
    ) {
        $this->entityUpdates = $entityUpdates;
        $this->entityTypeManager = $entityTypeManager;
    }

    public static function getSubscribedEvents()
    {
        $events[ConfigEvents::IMPORT][] = ['onConfigImportInitFields', 20];

        return $events;
    }

    public function onConfigImportInitFields(ConfigImporterEvent $event)
    {
        // TODO Does not seem to kick in..
        /** @var WmContentContainerInterface[] $containers */
        $containers = $this->entityTypeManager
            ->getStorage('wmcontent_container')
            ->loadMultiple();

        foreach ($containers as $container) {
            $this->entityUpdates->applyUpdates($container->getChildEntityType());
        }
    }
}
