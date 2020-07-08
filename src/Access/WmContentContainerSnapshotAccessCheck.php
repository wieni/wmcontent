<?php

namespace Drupal\wmcontent\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\wmcontent\WmContentContainerInterface;

class WmContentContainerSnapshotAccessCheck implements AccessInterface
{
    public function access(RouteMatchInterface $routeMatch, WmContentContainerInterface $container): AccessResultInterface
    {
        return AccessResult::allowedIf($container->hasSnapshotsEnabled());
    }
}
