<?php

namespace Drupal\wmcontent\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\wmcontent\Controller\WmContentChildController;
use Drupal\wmcontent\Form\WmContentMasterForm;
use Drupal\wmcontent\WmContentContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class WmContentRouteSubscriber extends RouteSubscriberBase
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager
    ) {
        $this->entityTypeManager = $entityTypeManager;
    }

    public static function getSubscribedEvents()
    {
        // Should run after AdminRouteSubscriber so the routes can inherit admin
        // status of the edit routes on entities. Therefore priority -210.
        $events[RoutingEvents::ALTER] = ['onAlterRoutes', -210];

        return $events;
    }

    protected function alterRoutes(RouteCollection $collection)
    {
        $storage = $this->entityTypeManager->getStorage('wmcontent_container');

        /** @var WmContentContainerInterface $container */
        foreach ($storage->loadMultiple() as $container) {
            $hostEntityTypeId = $container->getHostEntityType();
            $adminRoute = $this->isAdminRoute($collection, $hostEntityTypeId);

            $overviewRouteName = "entity.{$hostEntityTypeId}.wmcontent_overview";
            if (!$collection->get($overviewRouteName)) {
                $route = $this->getOverviewRoute($container, $adminRoute);
                $collection->add($overviewRouteName, $route);
            }

            $addRouteName = "entity.{$hostEntityTypeId}.wmcontent_add";
            if (!$collection->get($addRouteName)) {
                $route = $this->getAddRoute($container, $adminRoute);
                $collection->add($addRouteName, $route);
            }

            $editRouteName = "entity.{$hostEntityTypeId}.wmcontent_edit";
            if (!$collection->get($editRouteName)) {
                $route = $this->getEditRoute($container, $adminRoute);
                $collection->add($editRouteName, $route);
            }

            $deleteRouteName = "entity.{$hostEntityTypeId}.wmcontent_delete";
            if (!$collection->get($deleteRouteName)) {
                $route = $this->getDeleteRoute($container, $adminRoute);
                $collection->add($deleteRouteName, $route);
            }
        }
    }

    protected function getOverviewRoute(WmContentContainerInterface $container, bool $adminRoute): Route
    {
        $hostEntityTypeId = $container->getHostEntityType();

        return new Route(
            $this->getBasePath($hostEntityTypeId),
            [
                '_form' => WmContentMasterForm::class,
                '_title_callback' => WmContentMasterForm::class . '::title',
                'container' => $container->getId(),
            ],
            [
                '_entity_access' => $hostEntityTypeId . '.update',
                '_wmcontent_container_view_access' => $hostEntityTypeId,
            ],
            [
                'parameters' => [
                    'host' => [
                        'type' => 'entity:' . $hostEntityTypeId,
                    ],
                    'container' => [
                        'type' => 'entity:wmcontent_container',
                    ],
                ],
                '_admin_route' => $adminRoute,
            ]
        );
    }

    protected function getAddRoute(WmContentContainerInterface $container, bool $adminRoute): Route
    {
        $hostEntityTypeId = $container->getHostEntityType();

        return new Route(
            $this->getBasePath($hostEntityTypeId) . '/add/{bundle}',
            [
                '_controller' => WmContentChildController::class . '::add',
                '_title_callback' => WmContentChildController::class . '::addTitle',
            ],
            [
                '_entity_access' => $hostEntityTypeId . '.update',
            ],
            [
                'parameters' => [
                    'host' => [
                        'type' => 'entity:' . $hostEntityTypeId,
                    ],
                    'container' => [
                        'type' => 'entity:wmcontent_container',
                    ],
                ],
                '_admin_route' => $adminRoute,
            ]
        );
    }

    protected function getEditRoute(WmContentContainerInterface $container, bool $adminRoute): Route
    {
        $hostEntityTypeId = $container->getHostEntityType();
        $childEntityTypeId = $container->getChildEntityType();

        return new Route(
            $this->getBasePath($hostEntityTypeId) . '/{child}/edit',
            [
                '_controller' => WmContentChildController::class . '::edit',
                '_title_callback' => WmContentChildController::class . '::editTitle',
            ],
            [
                '_entity_access' => $hostEntityTypeId . '.update',
            ],
            [
                'parameters' => [
                    'host' => [
                        'type' => 'entity:' . $hostEntityTypeId,
                    ],
                    'child' => [
                        'type' => 'wmcontent-child:' . $childEntityTypeId,
                    ],
                    'container' => [
                        'type' => 'entity:wmcontent_container',
                    ],
                ],
                '_admin_route' => $adminRoute,
            ]
        );
    }

    protected function getDeleteRoute(WmContentContainerInterface $container, bool $adminRoute): Route
    {
        $hostEntityTypeId = $container->getHostEntityType();
        $childEntityTypeId = $container->getChildEntityType();

        return new Route(
            $this->getBasePath($hostEntityTypeId) . '/{child}/delete',
            [
                '_controller' => WmContentChildController::class . '::delete',
            ],
            [
                '_entity_access' => $hostEntityTypeId . '.update',
            ],
            [
                'parameters' => [
                    'host' => [
                        'type' => 'entity:' . $hostEntityTypeId,
                    ],
                    'child' => [
                        'type' => 'wmcontent-child:' . $childEntityTypeId,
                    ],
                    'container' => [
                        'type' => 'entity:wmcontent_container',
                    ],
                ],
                '_admin_route' => $adminRoute,
            ]
        );
    }

    protected function getBasePath(string $hostEntityTypeId): string
    {
        $hostType = $this->entityTypeManager
            ->getDefinition($hostEntityTypeId);

        $basePath = $hostType->getLinkTemplate('canonical');

        if (strpos($basePath, '/') !== false) {
            $basePath = '/' . $basePath;
        }

        $basePath .= '/wmcontent/{container}';

        return $basePath;
    }

    protected function isAdminRoute(RouteCollection $collection, string $hostEntityTypeId): bool
    {
        if (!$editRoute = $collection->get("entity.{$hostEntityTypeId}.edit_form")) {
            return false;
        }

        return (bool) $editRoute->getOption('_admin_route');
    }
}
