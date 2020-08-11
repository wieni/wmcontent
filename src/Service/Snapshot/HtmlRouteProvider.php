<?php

namespace Drupal\wmcontent\Service\Snapshot;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class HtmlRouteProvider extends AdminHtmlRouteProvider
{
    public function getRoutes(EntityTypeInterface $entityType): RouteCollection
    {
        $routes = parent::getRoutes($entityType);
        $routes->remove('entity.wmcontent_snapshot.canonical');

        $collection = $routes->get('entity.wmcontent_snapshot.collection');
        if ($collection) {
            $collection->setDefault('_title', '@label');
        }

        $routes->add(
            'entity.wmcontent_snapshot.export',
            new Route(
                '/admin/wmcontent/snapshot/{wmcontent_snapshot}/edit',
                [],
                [
                    '_permission' => 'administer wmcontent',
                ],
            )
        );

        return $routes;
    }
}
