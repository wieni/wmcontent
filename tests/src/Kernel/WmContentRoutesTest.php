<?php

namespace Drupal\Tests\wmcontent\Kernel;

use Symfony\Component\Routing\RouterInterface;

/**
 * @covers \Drupal\wmcontent\Routing\WmContentRouteSubscriber
 */
class WmContentRoutesTest extends WmContentTestBase
{
    /** @var RouterInterface */
    protected $router;

    protected function setUp()
    {
        parent::setUp();

        $this->router = $this->container->get('router');

        // Rebuild routes
        $this->container->get('router.builder')->rebuild();
    }

    public function testAreRoutesAdded(): void
    {
        $routeNames = [
            sprintf('entity.%s.wmcontent_overview', self::ENTITY_TYPE_ID),
            sprintf('entity.%s.wmcontent_add', self::ENTITY_TYPE_ID),
            sprintf('entity.%s.wmcontent_edit', self::ENTITY_TYPE_ID),
            sprintf('entity.%s.wmcontent_delete', self::ENTITY_TYPE_ID),
        ];

        foreach ($routeNames as $routeName) {
            self::assertNotNull(
                $this->router->getRouteCollection()->get($routeName),
                sprintf("Route with name '%s' is not added to host entity type with ID '%s'", $routeName, self::ENTITY_TYPE_ID)
            );
        }
    }
}
