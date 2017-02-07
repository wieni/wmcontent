<?php

namespace Drupal\wmcontent\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\wmcontent\Entity\WmContentContainer;

/**
 * Access check for wmcontent container overview.
 */
class WmContentContainerAccessCheck implements AccessInterface
{

    /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityManagerInterface
     */
    protected $entityTypeManager;


    /**
     * WmContentContainerAccessCheck constructor.
     *
     * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
     */
    public function __construct(EntityTypeManager $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * Checks access to the current container overview for the entity and bundle.
     *
     * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
     *   The parametrized route.
     * @param \Drupal\Core\Session\AccountInterface $account
     *   The currently logged in account.
     * @param string $host_type_id
     *   The entity type ID.
     *
     * @return \Drupal\Core\Access\AccessResultInterface
     *   The access result.
     */
    public function access(RouteMatchInterface $route_match, AccountInterface $account, $host_type_id)
    {
        /* @var \Drupal\Core\Entity\ContentEntityInterface $entity */
        $entity = $route_match->getParameter($host_type_id);

        /** @var WmContentContainer $container */
        $container = $this
            ->entityTypeManager
            ->getStorage('wmcontent_container')
            ->load($route_match->getParameter('container'));

        if ($entity && $container && $container->getId()) {
            // Get entity base info.
            $bundle = $entity->bundle();

            // If this bundle is in the list of host bundles, then allow.
            if (empty($container->getHostBundles())
                || array_key_exists($entity->bundle(), $container->getHostBundles())
            ) {
                return AccessResult::allowed();
            }
        }

        // No opinion.
        return AccessResult::neutral();
    }
}
