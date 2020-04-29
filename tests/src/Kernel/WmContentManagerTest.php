<?php

namespace Drupal\Tests\wmcontent\Kernel;

use Drupal\wmcontent\WmContentManagerInterface;

class WmContentManagerTest extends WmContentTestBase
{
    /** @var WmContentManagerInterface */
    protected $manager;

    protected function setUp()
    {
        parent::setUp();

        $this->manager = $this->container->get('wmcontent.manager');
    }

    /**
     * @covers \Drupal\wmcontent\WmContentManager::getContent
     * @covers \Drupal\wmcontent\WmContentManager::getHost
     * @covers \Drupal\wmcontent\WmContentManager::isChild
     */
    public function testGetContent(): void
    {
        $host = $this->createHost();
        $child = $this->createChild($host);

        self::assertCount(1, $this->manager->getContent($host, $this->contentContainer->id()));
        self::assertSame($host->id(), $this->manager->getHost($child)->id());
        self::assertTrue($this->manager->isChild($child));
    }

    /**
     * @covers \Drupal\wmcontent\WmContentManager::getContainers
     * @covers \Drupal\wmcontent\WmContentManager::getHostContainers
     * @covers \Drupal\wmcontent\WmContentManager::getChildContainers
     */
    public function testGetContainers(): void
    {
        $host = $this->createHost();
        $child = $this->createChild($host);

        self::assertCount(1, $this->manager->getContainers());
        self::assertCount(1, $this->manager->getHostContainers($host));
        self::assertCount(1, $this->manager->getChildContainers($child));
    }
}
