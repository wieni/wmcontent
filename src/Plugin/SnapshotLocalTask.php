<?php

namespace Drupal\tracker\Plugin\Menu;

use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides route parameters needed to link to the current snapshot tab.
 */
class SnapshotLocalTask extends LocalTaskDefault
{
    public function getRouteParameters(RouteMatchInterface $routeMatch)
    {
        return ['user' => $this->currentUser()->Id()];
    }
}
