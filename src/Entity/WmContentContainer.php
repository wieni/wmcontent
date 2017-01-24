<?php

namespace Drupal\wmcontent\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\wmcontent\WmContentContainerInterface;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;

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
 *     "child_bundles",
 *     "hide_single_option_sizes",
 *     "hide_single_option_alignments",
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
     * If the options for sizes is is just one option do we then
     * hide the field in the form?
     * @var bool
     */
    public $hide_single_option_sizes = false;

    /**
     * If the options for the alignments is just one option
     * then do we hide the field in the form.
     * @var bool
     */
    public $hide_single_option_alignments = false;

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
     * @return bool
     */
    public function getHideSingleOptionSizes()
    {
        return $this->hide_single_option_sizes;
    }

    /**
     * @return bool
     */
    public function getHideSingleOptionAlignments()
    {
        return $this->hide_single_option_alignments;
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
            'hide_single_option_sizes' => $this->getHideSingleOptionSizes(),
            'hide_single_option_alignments' => $this->getHideSingleOptionAlignments(),
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
    public function isHost(EntityInterface $host)
    {
        return $host->getEntityTypeId() == $this->getHostEntityType()
            && in_array($host->bundle(), $this->getHostBundles());
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
