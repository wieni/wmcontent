<?php

/**
 * @file
 * Contains \Drupal\wmcontent\WmContentContainerListBuilder.
 */

namespace Drupal\wmcontent;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of user ball entities.
 *
 * @see \Drupal\user\Entity\Ball
 */
class WmContentContainerListBuilder extends DraggableListBuilder
{
    /** @var MessengerInterface */
    protected $messenger;

    public static function createInstance(
        ContainerInterface $container,
        EntityTypeInterface $entityType
    ) {
        $instance = parent::createInstance($container, $entityType);
        $instance->messenger = $container->get('messenger');

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'wmcontent_entity_wmcontent_container_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildHeader()
    {
        $header = [
            'label' => t('Name'),
            'id' => t('Slug'),
            'host_entity_type' => t('Host Entity Type'),
            'host_bundles' => t('Host Entity Bundles'),
            'child_entity_type' => t('Child Entity Type'),
            'child_bundles' => t('Child Entity Bundles'),
        ];

        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity)
    {
        $row = [
            'label' => $entity->getLabel(),
            'id' => $entity->getLabel(),
            'host_entity_type' =>  $entity->getHostEntityType(),
            'host_bundles' => $entity->getHostBundles(),
            'child_entity_type' => $entity->getChildEntityType(),
            'child_bundles' =>  $entity->getChildBundles(),
        ];

        if (empty($row['host_bundles'])) {
            $row['host_bundles'] = $this->t('- All bundles -');
        } else {
            $row['host_bundles'] = implode(", ", $row['host_bundles']);
        }

        if (empty($row['child_bundles'])) {
            $row['child_bundles'] = $this->t('- All bundles -');
        } else {
            $row['child_bundles'] = implode(", ", $row['child_bundles']);
        }


        return $row + parent::buildRow($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOperations(EntityInterface $entity)
    {
        $operations = parent::getDefaultOperations($entity);

        if ($entity->hasLinkTemplate('edit-form')) {
            $operations['edit'] = array(
                'title' => t('Edit container'),
                'weight' => 20,
                'url' => $entity->toUrl('edit-form'),
            );
        }

        return $operations;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        parent::submitForm($form, $form_state);

        $this->messenger->addStatus(t('The container settings have been updated.'));
    }
}
