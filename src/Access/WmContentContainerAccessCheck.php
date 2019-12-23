<?php

namespace Drupal\wmcontent\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\wmcontent\WmContentContainerInterface;

class WmContentContainerAccessCheck implements AccessInterface
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager
    ) {
        $this->entityTypeManager = $entityTypeManager;
    }

    public function access(RouteMatchInterface $routeMatch, AccountInterface $account, ?string $host_type_id = null): AccessResultInterface
    {
        /* @var EntityInterface $entity */
        $entity = $routeMatch->getParameter($host_type_id);

        /** @var WmContentContainerInterface $container */
        $container = $this->entityTypeManager
            ->getStorage('wmcontent_container')
            ->load($routeMatch->getParameter('container'));

        if ($entity && $container) {
            if (
                empty($container->getHostBundles())
                || array_key_exists($entity->bundle(), $container->getHostBundles())
            ) {
                return AccessResult::allowed();
            }
        }

        return AccessResult::neutral();
    }
}
