<?php

namespace Drupal\wmcontent\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\wmcontent\WmContentContainerInterface;

/**
 * Defines the wmcontent_container entity.
 *
 * @ConfigEntityType(
 *     id = "wmcontent_container",
 *     label = @Translation("WmContent Container"),
 *     handlers = {
 *         "list_builder" : "Drupal\wmcontent\WmContentContainerListBuilder",
 *         "form" : {
 *             "add" : "Drupal\wmcontent\Form\WmContentContainerForm",
 *             "edit" : "Drupal\wmcontent\Form\WmContentContainerForm",
 *             "delete" : "Drupal\wmcontent\Form\WmContentContainerDeleteForm",
 *         }
 *     },
 *     config_prefix = "wmcontent_container",
 *     admin_permission = "administer wmcontent",
 *     entity_keys = {
 *         "id" : "id",
 *         "label" : "label",
 *     },
 *     links = {
 *         "collection" : "/admin/config/wmcontent/containers",
 *         "add-form" : "/admin/config/wmcontent/containers/add",
 *         "edit-form" : "/admin/config/wmcontent/containers/{wmcontent_container}",
 *         "delete-form" : "/admin/config/wmcontent/containers/{wmcontent_container}/delete",
 *     },
 *     config_export = {
 *         "id",
 *         "label",
 *         "host_entity_type",
 *         "host_bundles",
 *         "child_entity_type",
 *         "child_bundles",
 *         "child_bundles_default",
 *         "hide_single_option_sizes",
 *         "hide_single_option_alignments",
 *         "show_size_column",
 *         "show_alignment_column"
 *     }
 * )
 */
class WmContentContainer extends ConfigEntityBase implements WmContentContainerInterface
{
    /** @var string */
    public $id;
    /** @var string */
    public $label;
    /** @var string */
    public $host_entity_type = '';
    /** @var array */
    public $host_bundles = [];
    /** @var string */
    public $child_entity_type = '';
    /** @var array */
    public $child_bundles = [];
    /** @var string */
    public $child_bundles_default;
    /** @var bool */
    public $hide_single_option_sizes = false;
    /** @var bool */
    public $hide_single_option_alignments = false;
    /** @var bool */
    public $show_size_column = true;
    /** @var bool */
    public $show_alignment_column = true;

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getHostEntityType(): string
    {
        return $this->host_entity_type;
    }

    public function getHostBundles(): array
    {
        return $this->host_bundles;
    }

    public function getHostBundlesAll(): array
    {
        return $this->getAllBundles($this->getHostEntityType());
    }

    public function getChildEntityType(): string
    {
        return $this->child_entity_type;
    }

    public function getChildBundles(): array
    {
        return $this->child_bundles;
    }

    public function getChildBundlesAll(): array
    {
        return $this->getAllBundles($this->getChildEntityType());
    }

    public function getChildBundlesDefault(): string
    {
        return $this->child_bundles_default;
    }

    public function getHideSingleOptionSizes(): bool
    {
        return $this->hide_single_option_sizes;
    }

    public function getHideSingleOptionAlignments(): bool
    {
        return $this->hide_single_option_alignments;
    }

    public function getShowSizeColumn(): bool
    {
        return $this->show_size_column;
    }

    public function getShowAlignmentColumn(): bool
    {
        return $this->show_alignment_column;
    }

    public function getConfig(): array
    {
        $config = [
            'id' => $this->getId(),
            'label' => $this->getLabel(),
            'host_entity_type' => $this->getHostEntityType(),
            'child_entity_type' => $this->getChildEntityType(),
            'host_bundles' => $this->getHostBundles(),
            'child_bundles' => $this->getChildBundles(),
            'child_bundles_default' => $this->getChildBundlesDefault(),
            'hide_single_option_sizes' => $this->getHideSingleOptionSizes(),
            'hide_single_option_alignments' => $this->getHideSingleOptionAlignments(),
            'show_size_column' => $this->getShowSizeColumn(),
            'show_alignment_column' => $this->getShowAlignmentColumn(),
        ];

        if (empty($config['host_bundles'])) {
            $config['host_bundles'] = $this->getHostBundlesAll();
        }

        if (empty($config['child_bundles'])) {
            $config['child_bundles'] = $this->getChildBundlesAll();
        }

        return $config;
    }

    public function isHost(EntityInterface $host): bool
    {
        return $host->getEntityTypeId() === $this->getHostEntityType()
            && (
                empty($this->getHostBundles())
                || in_array($host->bundle(), $this->getHostBundles())
            );
    }

    public function hasChild(EntityInterface $child): bool
    {
        $childBundles = $this->getChildBundles();
        return $this->getChildEntityType() === $child->getEntityTypeId()
            && (
                empty($childBundles)
                || array_key_exists($child->bundle(), $childBundles)
            );
    }

    public function postSave(EntityStorageInterface $storage, $update = false)
    {
        parent::postSave($storage, $update);

        // Add our base fields to the schema.
        \Drupal::service('wmcontent.entity_updates')
            ->applyUpdates($this->getChildEntityType());
        drupal_flush_all_caches();
    }

    public static function postDelete(EntityStorageInterface $storage, array $entities)
    {
        parent::postDelete($storage, $entities);

        // Add our base fields to the schema.
        foreach ($entities as $entity) {
            \Drupal::service('wmcontent.entity_updates')
                ->applyUpdates($entity->getChildEntityType());
        }
        drupal_flush_all_caches();
    }

    protected function getAllBundles(string $entityTypeId): array
    {
        $bundles = $this->entityTypeBundleInfo()
            ->getBundleInfo($entityTypeId);
        $bundles = array_keys($bundles);

        sort($bundles);

        return array_combine($bundles, $bundles);
    }
}
