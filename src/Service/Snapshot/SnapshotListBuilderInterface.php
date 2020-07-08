<?php

namespace Drupal\wmcontent\Service\Snapshot;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilderInterface;
use Drupal\wmcontent\WmContentContainerInterface;

interface SnapshotListBuilderInterface extends EntityListBuilderInterface
{
    public function setContainer(WmContentContainerInterface $container): void;

    public function setHost(EntityInterface $host): void;
}
