<?php

namespace Drupal\wmcontent;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityInterface;

interface WmContentContainerInterface extends ConfigEntityInterface
{
    public function getLabel(): string;

    public function getId(): string;

    public function getHostEntityType(): string;

    public function getHostBundles(): array;

    public function getHostBundlesAll(): array;

    public function getChildEntityType(): string;

    public function getChildBundles(): array;

    public function getChildBundlesDefault(): ?string;

    public function getChildBundlesAll(): array;

    public function getConfig(): array;

    public function getHideSingleOptionSizes(): bool;

    public function getHideSingleOptionAlignments(): bool;

    public function getShowSizeColumn(): bool;

    public function getShowAlignmentColumn(): bool;

    public function isHost(EntityInterface $host): bool;

    public function hasChild(EntityInterface $child): bool;

    public function hasSnapshotsEnabled(): bool;
}
