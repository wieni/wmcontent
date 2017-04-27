<?php

namespace Drupal\wmcontent\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\wmcontent\Entity\WmContentContainer;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for entity wmcontent routes.
 */
class WmContentRouteSubscriber extends RouteSubscriberBase
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;

    /**
     * WmContentRouteSubscriber constructor.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     */
    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function alterRoutes(RouteCollection $collection)
    {
        $storage = $this->entityTypeManager->getStorage('wmcontent_container');

        /** @var WmContentContainer $container */
        foreach ($storage->loadMultiple() as $container) {
            // Get the config.
            $config = $container->getConfig();

            // Load the host type.
            $host_type = $this->entityTypeManager->getDefinition($config['host_entity_type']);

            // Try to get the route from the current collection.
            $link_template = $host_type->getLinkTemplate('canonical');
            if (strpos($link_template, '/') !== false) {
                $base_path = '/' . $link_template;
            } else {
                if (!$entity_route = $collection->get('entity.' . $config['host_entity_type'] . '.canonical')) {
                    continue;
                }
                $base_path = $entity_route->getPath();
            }

            // Inherit admin route status from edit route, if exists.
            $is_admin = false;
            $route_name = 'entity.' . $config['host_entity_type'] . '.edit_form';
            $edit_route = $collection->get($route_name);
            if ($edit_route) {
                $is_admin = (bool) $edit_route->getOption('_admin_route');
            }

            // Set a base path.
            $path = $base_path . '/wmcontent/{container}';


            // Overview.
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
                  '_admin_route' => $is_admin,
                ]
            );
            $route_name = 'entity.' . $config['host_entity_type'] . '.wmcontent_overview';

            $collection->add($route_name, $route);

            // Add.
            $route = new Route(
                $path . '/add/{bundle}',
                [
                  '_controller' => '\Drupal\wmcontent\Controller\WmContentController::add',
                  '_title_callback' => '\Drupal\wmcontent\WmContentDescriptiveTitles::getTitle',
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
                  '_admin_route' => $is_admin,
                ]
            );
            $collection->add('entity.' . $config['host_entity_type'] . '.wmcontent_add', $route);

            // Edit.
            $route = new Route(
                $path . '/{child_id}/edit',
                [
                  '_controller' => '\Drupal\wmcontent\Controller\WmContentController::edit',
                    '_title_callback' => '\Drupal\wmcontent\WmContentDescriptiveTitles::getTitle',
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
                  '_admin_route' => $is_admin,
                ]
            );
            $collection->add('entity.' . $config['host_entity_type'] . '.wmcontent_edit', $route);

            // Delete.
            $route = new Route(
                $path . '/{child_id}/delete',
                [
                    '_controller' => '\Drupal\wmcontent\Controller\WmContentController::delete',
                    '_title_callback' => '\Drupal\wmcontent\WmContentDescriptiveTitles::getTitle',
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
                      '_admin_route' => $is_admin,
                ]
            );
            $collection->add('entity.' . $config['host_entity_type'] . '.wmcontent_delete', $route);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        $events = parent::getSubscribedEvents();
        // Should run after AdminRouteSubscriber so the routes can inherit admin
        // status of the edit routes on entities. Therefore priority -210.
        $events[RoutingEvents::ALTER] = ['onAlterRoutes', -210];
        return $events;
    }
}
