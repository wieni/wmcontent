<?php

namespace Drupal\wmcontent\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\wmcontent\WmContentContainerInterface;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the wmcontent_container entity.
 *
 * @ConfigEntityType(
 *   id = "wmcontent_container",
 *   label = @Translation("WmContent Container"),
 *   handlers = {
 *     "list_builder" = "Drupal\wmcontent\WmContentContainerListBuilder",
 *     "form" = {
 *       "add" = "Drupal\wmcontent\Form\WmContentContainerForm",
 *       "edit" = "Drupal\wmcontent\Form\WmContentContainerForm",
 *       "delete" = "Drupal\wmcontent\Form\WmContentContainerDeleteForm",
 *     }
 *   },
 *   config_prefix = "wmcontent_container",
 *   admin_permission = "administer wmcontent",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "collection" = "/admin/config/wmcontent/containers",
 *     "add-form" = "/admin/config/wmcontent/containers/add",
 *     "edit-form" = "/admin/config/wmcontent/containers/{wmcontent_container}",
 *     "delete-form" = "/admin/config/wmcontent/containers/{wmcontent_container}/delete",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "host_entity_type",
 *     "host_bundles",
 *     "child_entity_type",
 *     "child_bundles"
 *   }
 * )
 */
class WmContentContainer extends ConfigEntityBase implements WmContentContainerInterface
{

    /**
     * The WmContent Container ID.
     *
     * @var string
     */
    public $id;

    /**
     * The WmContent Container label.
     *
     * @var string
     */
    public $label;

    /**
     * The WmContent Container host entity type.
     *
     * @var string
     */
    public $host_entity_type = '';

    /**
     * The WmContent Container host entity bundles.
     *
     * @var array
     */
    public $host_bundles = [];

    /**
     * The WmContent Container child entity types.
     *
     * @var string
     */
    public $child_entity_type = '';

    /**
     * The WmContent Container child entity bundles.
     *
     * @var array
     */
    public $child_bundles = [];

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getHostEntityType()
    {
        return $this->host_entity_type;
    }

    /**
     * {@inheritdoc}
     */
    public function getHostBundles()
    {
        return $this->host_bundles;
    }

    /**
     * {@inheritdoc}
     */
    public function getChildEntityType()
    {
        return $this->child_entity_type;
    }


    /**
     * {@inheritdoc}
     */
    public function getChildBundles()
    {
        return $this->child_bundles;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig() {
        $config = [
            'id' => $this->getId(),
            'label' => $this->getLabel(),
            'host_entity_type' => $this->getHostEntityType(),
            'child_entity_type' => $this->getChildEntityType(),
            'host_bundles' => $this->getHostBundles(),
            'child_bundles' => $this->getChildBundles(),
        ];

        if (empty($config['host_bundles'])) {
            // Load them all.
            $config['host_bundles'] = array_keys($this->entityTypeManager()->getBundleInfo($this->getHostEntityType()));
        }
        if (empty($config['child_bundles'])) {
            // Load them all.
            $config['child_bundles'] = array_keys($this->entityManager()->getBundleInfo($this->getChildEntityType()));
        }

        return $config;
    }

    /**
    * {@inheritdoc}
    */
    public function postSave(EntityStorageInterface $storage, $update = false)
    {
        parent::postSave($storage, $update);

        // Add our base fields to the schema.
        \Drupal::service('entity.definition_update_manager')->applyUpdates();
        drupal_flush_all_caches();
    }

    /**
    * {@inheritdoc}
    */
    public static function postDelete(EntityStorageInterface $storage, array $entities)
    {
        parent::postDelete($storage, $entities);

        // Add our base fields to the schema.
        \Drupal::service('entity.definition_update_manager')->applyUpdates();
        drupal_flush_all_caches();
    }

}
