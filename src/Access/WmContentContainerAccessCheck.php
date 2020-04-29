<?php

namespace Drupal\wmcontent\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\wmcontent\WmContentContainerInterface;

class WmContentContainerAccessCheck implements AccessInterface
{
    public function access(RouteMatchInterface $routeMatch, WmContentContainerInterface $container): AccessResultInterface
    {
        /* @var ContentEntityInterface $entity */
        $host = $routeMatch->getParameter($container->getHostEntityType());

        if (empty($container->getHostBundles())) {
            return AccessResult::allowed();
        }

        if ($host && array_key_exists($host->bundle(), $container->getHostBundles())) {
            return AccessResult::allowed();
        }

        return AccessResult::neutral();
    }
}
