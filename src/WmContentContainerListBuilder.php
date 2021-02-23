<?php

namespace Drupal\wmcontent;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WmContentContainerListBuilder extends DraggableListBuilder
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var MessengerInterface */
    protected $messenger;

    public static function createInstance(
        ContainerInterface $container,
        EntityTypeInterface $entityType
    ) {
        $instance = parent::createInstance($container, $entityType);
        $instance->entityTypeManager = $container->get('entity_type.manager');
        $instance->messenger = $container->get('messenger');

        return $instance;
    }

    public function getFormId(): string
    {
        return 'wmcontent_entity_wmcontent_container_form';
    }

    public function buildHeader(): array
    {
        $header = [
            'label' => $this->t('Name'),
            'id' => $this->t('Slug'),
            'host_entity_type' => $this->t('Host entity type'),
            'host_bundles' => $this->t('Host entity bundles'),
            'child_entity_type' => $this->t('Child entity type'),
            'child_bundles' => $this->t('Child entity bundles'),
        ];

        return $header + parent::buildHeader();
    }

    public function buildRow(EntityInterface $entity): array
    {
        $row = [
            'label' => $entity->getLabel(),
            'id' => $entity->getLabel(),
            'host_entity_type' => $this->entityTypeManager
                ->getDefinition($entity->getHostEntityType())
                ->getLabel(),
            'host_bundles' => $entity->getHostBundles(),
            'child_entity_type' => $this->entityTypeManager
                ->getDefinition($entity->getChildEntityType())
                ->getLabel(),
            'child_bundles' => $entity->getChildBundles(),
        ];

        if (empty($row['host_bundles'])) {
            $row['host_bundles'] = $this->t('- All bundles -');
        } else {
            $row['host_bundles'] = implode(', ', $row['host_bundles']);
        }

        if (empty($row['child_bundles'])) {
            $row['child_bundles'] = $this->t('- All bundles -');
        } else {
            $row['child_bundles'] = implode(', ', $row['child_bundles']);
        }

        return $row + parent::buildRow($entity);
    }

    public function getDefaultOperations(EntityInterface $entity)
    {
        $operations = parent::getDefaultOperations($entity);

        if ($entity->hasLinkTemplate('edit-form')) {
            $operations['edit'] = [
                'title' => t('Edit container'),
                'weight' => 20,
                'url' => $entity->toUrl('edit-form'),
            ];
        }

        return $operations;
    }

    public function submitForm(array &$form, FormStateInterface $formState)
    {
        parent::submitForm($form, $formState);

        $this->messenger->addStatus($this->t('The container settings have been updated.'));
    }
}
