<?php

namespace Drupal\wmcontent\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal;

/**
 * Subscriber for entity wmcontent routes.
 */
class WmContentRouteSubscriber extends RouteSubscriberBase
{

    /**
     * The Entity Manager interface.
     *
     * @var \Drupal\Core\Entity\EntityManagerInterface
     */
    protected $entityManager;

    /**
     * Constructs a ContentTranslationRouteSubscriber object.
     *
     * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
     *   The wmcontent container.
     */
    public function __construct(EntityManagerInterface $entity_manager)
    {
        $this->entityManager = $entity_manager;
    }

    /**
     * {@inheritdoc}
     */
    protected function alterRoutes(RouteCollection $collection)
    {
        $storage = $this->entityManager->getStorage('wmcontent_container');

        foreach ($storage->loadMultiple() as $container) {
            $config = $container->getConfig();

            // Load the host type.
            $host_type = $this->entityManager->getDefinition($config['host_entity_type']);

            // Add a route for each enabled host entity. We can't Do this per bundle
            // so we'll still have to fix it via access hooks. TODO TODO :)

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
            if ($edit_route = $collection->get($route_name)) {
                $is_admin = (bool) $edit_route->getOption('_admin_route');
            }

            // Set a base path.
            $path = $base_path . '/content/{container}';


            // Overview.
            $route = new Route(
                $path,
                array(

                    '_controller' => '\Drupal\wmcontent\Controller\WmContentController::overview',
                    'host_type_id' => $config['host_entity_type'],
                    'container' => $config['id'],
                ),
                array(
                    '_entity_access' => $config['host_entity_type'] . '.update',
                    '_wmcontent_container_view_access' => $config['host_entity_type'],
                ),
                array(
                  'parameters' => array(
                    $config['host_entity_type'] => array(
                      'type' => 'entity:' . $config['host_entity_type'],
                    ),
                  ),
                  '_admin_route' => $is_admin,
                )
            );
            $route_name = 'entity.' . $config['host_entity_type'] . '.wmcontent_overview';

            $collection->add($route_name, $route);

            // Add.
            $route = new Route(
                $path . '/add/{bundle}',
                array(
                  '_controller' => '\Drupal\wmcontent\Controller\WmContentController::add',
                  '_title' => 'Add',
                  'host_type_id' => $config['host_entity_type'],
                ),
                array(
                  '_entity_access' => $config['host_entity_type'] . '.update',
                ),
                array(
                  'parameters' => array(
                    $config['host_entity_type'] => array(
                      'type' => 'entity:' . $config['host_entity_type'],
                    ),
                  ),
                  '_admin_route' => $is_admin,
                )
            );
            $collection->add('entity.' . $config['host_entity_type'] . '.wmcontent_add', $route);

            // Edit.
            $route = new Route(
                $path . '/{child_id}/edit',
                array(
                  '_controller' => '\Drupal\wmcontent\Controller\WmContentController::edit',
                  '_title' => 'Edit',
                  'host_type_id' => $config['host_entity_type'],
                ),
                array(
                  '_entity_access' => $config['host_entity_type'] . '.update',
                ),
                array(
                  'parameters' => array(
                    $config['host_entity_type'] => array(
                      'type' => 'entity:' . $config['host_entity_type'],
                    ),
                  ),
                  '_admin_route' => $is_admin,
                )
            );
            $collection->add('entity.' . $config['host_entity_type'] . '.wmcontent_edit', $route);

            // Delete.
            $route = new Route(
                $path . '/{child_id}/delete',
                array(
                    '_controller' => '\Drupal\wmcontent\Controller\WmContentController::delete',
                    '_title' => 'Delete',
                    'host_type_id' => $config['host_entity_type'],
                ),
                array(
                    '_entity_access' => $config['host_entity_type'] . '.update',
                ),
                array(
                      'parameters' => array(
                            $config['host_entity_type'] => array(
                                'type' => 'entity:' . $config['host_entity_type'],
                            ),
                      ),
                      '_admin_route' => $is_admin,
                )
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
        $events[RoutingEvents::ALTER] = array('onAlterRoutes', -210);
        return $events;
    }
}
