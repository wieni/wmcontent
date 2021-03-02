<?php

namespace Drupal\Tests\wmcontent\Kernel;

use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeTypeInterface;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\wmcontent\Controller\WmContentChildController;
use Drupal\wmcontent\Entity\WmContentContainer;
use Drupal\wmcontent\WmContentContainerInterface;

abstract class WmContentTestBase extends KernelTestBase
{
    use UserCreationTrait;

    protected const ENTITY_TYPE_ID = 'node';
    protected const CHILD_BUNDLE = 'child_bundle';
    protected const HOST_BUNDLE = 'host_bundle';

    public static $modules = [
        'node',
        'options',
        'system',
        'user',
        'wmcontent',
    ];

    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var ControllerResolverInterface */
    protected $controllerResolver;

    /** @var NodeTypeInterface */
    protected $childType;
    /** @var NodeTypeInterface */
    protected $hostType;
    /** @var WmContentContainer */
    protected $contentContainer;

    protected function setUp()
    {
        parent::setUp();

        // Install config and schema
        $this->installSchema('node', 'node_access');
        $this->installEntitySchema('node');
        $this->installEntitySchema('user');
        $this->installConfig(['system']);

        // Set up dependencies
        $this->entityTypeManager = $this->container->get('entity_type.manager');
        $this->controllerResolver = $this->container->get('controller_resolver');

        // Create entities
        $this->setUpCurrentUser();

        $this->childType = NodeType::create(['type' => self::CHILD_BUNDLE]);
        $this->childType->save();

        $this->hostType = NodeType::create(['type' => self::HOST_BUNDLE]);
        $this->hostType->save();

        $this->contentContainer = $this->createContainer();
    }

    protected function createContainer(): WmContentContainerInterface
    {
        $container = WmContentContainer::create([
            'id' => $this->randomMachineName(5),
            'label' => $this->randomString(5),
            'host_entity_type' => self::ENTITY_TYPE_ID,
            'host_bundles' => [self::HOST_BUNDLE => self::HOST_BUNDLE],
            'child_entity_type' => self::ENTITY_TYPE_ID,
            'child_bundles' => [self::CHILD_BUNDLE => self::CHILD_BUNDLE],
            'child_bundles_default' => self::CHILD_BUNDLE,
            'hide_single_option_sizes' => false,
            'hide_single_option_alignments' => false,
            'show_size_column' => true,
            'show_alignment_column' => true,
        ]);
        $container->save();

        return $container;
    }

    protected function createHost(): ContentEntityInterface
    {
        $storage = $this->entityTypeManager->getStorage(self::ENTITY_TYPE_ID);
        $host = $storage->create([
            'type' => self::HOST_BUNDLE,
            'title' => 'Foo',
        ]);
        $host->save();

        return $host;
    }

    protected function createChild(ContentEntityInterface $host): ContentEntityInterface
    {
        /** @var WmContentChildController $controller */
        $controller = $this->controllerResolver->getControllerFromDefinition(WmContentChildController::class);

        $method = new \ReflectionMethod($controller, 'createChildEntity');
        $method->setAccessible(true);

        $child = $method->invokeArgs($controller, [$this->contentContainer, self::CHILD_BUNDLE, $host]);
        $child->set('title', 'Bar');
        $child->save();

        return $child;
    }
}
