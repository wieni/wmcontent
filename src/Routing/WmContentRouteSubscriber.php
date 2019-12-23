<?php

namespace Drupal\wmcontent\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
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
            $config = $container->getConfig();
            $hostType = $this->entityTypeManager->getDefinition($config['host_entity_type']);

            // Try to get the route from the current collection.
            $linkTemplate = $hostType->getLinkTemplate('canonical');
            if (strpos($linkTemplate, '/') !== false) {
                $basePath = '/' . $linkTemplate;
            } else {
                if (!$entityRoute = $collection->get('entity.' . $config['host_entity_type'] . '.canonical')) {
                    continue;
                }
                $basePath = $entityRoute->getPath();
            }

            // Inherit admin route status from edit route, if exists.
            $isAdmin = false;
            $routeName = 'entity.' . $config['host_entity_type'] . '.edit_form';

            if ($editRoute = $collection->get($routeName)) {
                $isAdmin = (bool) $editRoute->getOption('_admin_route');
            }

            $path = $basePath . '/wmcontent/{container}';

            $route = new Route(
                $path,
                [
                    '_controller' => '\Drupal\wmcontent\Controller\WmContentController::overview',
                    'host_type_id' => $config['host_entity_type'],
                    'container' => $config['id'],
                ],
                [
                    '_entity_access' => $config['host_entity_type'] . '.update',
                    '_wmcontent_container_view_access' => $config['host_entity_type'],
                ],
                [
                    'parameters' => [
                        $config['host_entity_type'] => [
                            'type' => 'entity:' . $config['host_entity_type'],
                        ],
                    ],
                    '_admin_route' => $isAdmin,
                ]
            );
            $collection->add('entity.' . $config['host_entity_type'] . '.wmcontent_overview', $route);

            $route = new Route(
                $path . '/add/{bundle}',
                [
                    '_controller' => '\Drupal\wmcontent\Controller\WmContentController::add',
                    '_title_callback' => 'wmcontent.descriptive_titles:getPageTitle',
                    'host_type_id' => $config['host_entity_type'],
                ],
                [
                    '_entity_access' => $config['host_entity_type'] . '.update',
                ],
                [
                    'parameters' => [
                        $config['host_entity_type'] => [
                            'type' => 'entity:' . $config['host_entity_type'],
                        ],
                    ],
                    '_admin_route' => $isAdmin,
                ]
            );
            $collection->add('entity.' . $config['host_entity_type'] . '.wmcontent_add', $route);

            $route = new Route(
                $path . '/{child_id}/edit',
                [
                    '_controller' => '\Drupal\wmcontent\Controller\WmContentController::edit',
                    '_title_callback' => 'wmcontent.descriptive_titles:getPageTitle',
                    'host_type_id' => $config['host_entity_type'],
                ],
                [
                    '_entity_access' => $config['host_entity_type'] . '.update',
                ],
                [
                    'parameters' => [
                        $config['host_entity_type'] => [
                            'type' => 'entity:' . $config['host_entity_type'],
                        ],
                    ],
                    '_admin_route' => $isAdmin,
                ]
            );
            $collection->add('entity.' . $config['host_entity_type'] . '.wmcontent_edit', $route);

            $route = new Route(
                $path . '/{child_id}/delete',
                [
                    '_controller' => '\Drupal\wmcontent\Controller\WmContentController::delete',
                    '_title_callback' => 'wmcontent.descriptive_titles:getPageTitle',
                    'host_type_id' => $config['host_entity_type'],
                ],
                [
                    '_entity_access' => $config['host_entity_type'] . '.update',
                ],
                [
                    'parameters' => [
                        $config['host_entity_type'] => [
                            'type' => 'entity:' . $config['host_entity_type'],
                        ],
                    ],
                    '_admin_route' => $isAdmin,
                ]
            );
            $collection->add('entity.' . $config['host_entity_type'] . '.wmcontent_delete', $route);
        }
    }
}
