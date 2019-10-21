<?php

namespace Drupal\wmcontent\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\wmcontent\Entity\WmContentContainer;
use Drupal\wmcontent\EntityUpdateService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Config subscriber.
 */
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

    /**
     * Checks that the Configuration module is not being uninstalled.
     *
     * @param ConfigImporterEvent $event
     *   The config import event.
     */
    public function onConfigImportInitFields(ConfigImporterEvent $event)
    {
        // TODO Does not seem to kick in..
        /** @var WmContentContainer[] $containers */
        $containers = $this->entityTypeManager
            ->getStorage('wmcontent_container')
            ->loadMultiple();

        foreach ($containers as $container) {
            $this->entityUpdates->applyUpdates($container->getChildEntityType());
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        $events[ConfigEvents::IMPORT][] = array('onConfigImportInitFields', 20);
        return $events;
    }
}
