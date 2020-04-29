<?php

namespace Drupal\Tests\wmcontent\Kernel;

use Drupal\wmcontent\Controller\WmContentChildController;

/**
 * @covers \Drupal\wmcontent\Controller\WmContentChildController::add
 * @covers \Drupal\wmcontent\Controller\WmContentChildController::edit
 */
class ChildEntityControllerTest extends WmContentTestBase
{
    public function testChildEntityCrudRoutes(): void
    {
        /** @var WmContentChildController $controller */
        $controller = $this->controllerResolver->getControllerFromDefinition(WmContentChildController::class);
        $host = $this->createHost();
        $child = $this->createChild($host);

        // Create
        $form = $controller->add($this->contentContainer, self::CHILD_BUNDLE, $host);
        self::assertEquals('node_child_bundle_form', $form['#form_id'], 'The controller did not return a child entity form');

        // Edit
        $form = $controller->edit($child);
        self::assertEquals('node_child_bundle_form', $form['#form_id'], 'The controller did not return a child entity form');
    }
}
