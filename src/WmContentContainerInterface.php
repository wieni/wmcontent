<?php

namespace Drupal\wmcontent;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface defining a Example entity.
 */
interface WmContentContainerInterface extends ConfigEntityInterface
{
    public function getLabel();
    public function getId();
    public function getHostEntityType();
    public function getHostBundles();
    public function getHostBundlesAll();
    public function getChildEntityType();
    public function getChildBundles();
    public function getChildBundlesDefault();
    public function getChildBundlesAll();
    public function getConfig();
    public function getHideSingleOptionSizes();
    public function getHideSingleOptionAlignments();
    public function isHost(EntityInterface $host);
    public function getShowSizeColumn();
    public function getShowAlignmentColumn();
}
