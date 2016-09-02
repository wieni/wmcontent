<?php

namespace Drupal\wmcontent\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Config subscriber.
 */
class WmContentConfigSubscriber implements EventSubscriberInterface
{

    /**
     * Checks that the Configuration module is not being uninstalled.
     *
     * @param ConfigImporterEvent $event
     *   The config import event.
     */
    public function onConfigImportInitFields(ConfigImporterEvent $event)
    {
        // TODO Does not seem to kick in..
        \Drupal::service('entity.definition_update_manager')->applyUpdates();
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
