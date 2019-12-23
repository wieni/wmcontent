<?php

namespace Drupal\wmcontent;

use Drupal\Core\Entity\EntityInterface;

interface WmContentManagerInterface
{
    /** @return EntityInterface[] */
    public function getContent(EntityInterface $host, string $containerId): array;

    public function getHost(EntityInterface $child): EntityInterface;

    public function isChild(EntityInterface $child): bool;

    /** @return WmContentContainerInterface[] */
    public function getContainers(): array;

    /** @return WmContentContainerInterface[] */
    public function getHostContainers(EntityInterface $host): array;

    /** @return WmContentContainerInterface[] */
    public function getChildContainers(EntityInterface $child): array;
}
