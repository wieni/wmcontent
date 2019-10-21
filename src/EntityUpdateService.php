<?php

namespace Drupal\wmcontent;

use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityTypeListenerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Schema\EntityStorageSchemaInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionListenerInterface;

class EntityUpdateService
{
    /** @var EntityDefinitionUpdateManagerInterface */
    protected $entityDefinitionUpdateManager;
    /** @var EntityLastInstalledSchemaRepositoryInterface */
    protected $entityLastInstalledSchemaRepository;
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var EntityTypeListenerInterface */
    protected $entityTypeListener;
    /** @var EntityFieldManagerInterface */
    protected $entityFieldManager;
    /** @var FieldStorageDefinitionListenerInterface */
    protected $fieldStorageDefinitionListener;

    public function __construct(
        EntityDefinitionUpdateManagerInterface $entityDefinitionUpdateManager,
        EntityLastInstalledSchemaRepositoryInterface $entityLastInstalledSchemaRepository,
        EntityTypeManagerInterface $entityTypeManager,
        EntityTypeListenerInterface $entityTypeListener,
        EntityFieldManagerInterface $entityFieldManager,
        FieldStorageDefinitionListenerInterface $fieldStorageDefinitionListener
    ) {
        $this->entityDefinitionUpdateManager = $entityDefinitionUpdateManager;
        $this->entityLastInstalledSchemaRepository = $entityLastInstalledSchemaRepository;
        $this->entityTypeManager = $entityTypeManager;
        $this->entityTypeListener = $entityTypeListener;
        $this->entityFieldManager = $entityFieldManager;
        $this->fieldStorageDefinitionListener = $fieldStorageDefinitionListener;
    }

    public function applyUpdates(string $childEntityTypeId): void
    {
        if (version_compare(\Drupal::VERSION, '8.7.0', '<')) {
            $this->entityDefinitionUpdateManager->applyUpdates();
            return;
        }

        $changeList = array_intersect_key(
            $this->entityDefinitionUpdateManager->getChangeList(),
            [$childEntityTypeId => 1, $childEntityTypeId . '_type' => 1],
        );

        if ($changeList) {
            // EntityDefinitionUpdateManagerInterface::getChangeList() only disables
            // the cache and does not invalidate. In case there are changes,
            // explicitly invalidate caches.
            $this->entityTypeManager->clearCachedDefinitions();
            $this->entityFieldManager->clearCachedFieldDefinitions();
        }

        foreach ($changeList as $entityTypeId => $entityTypeChanges) {
            // Process entity type definition changes before storage definitions ones
            // this is necessary when you change an entity type from non-revisionable
            // to revisionable and at the same time add revisionable fields to the
            // entity type.
            if (!empty($entityTypeChanges['entity_type'])) {
                $this->doEntityUpdate($entityTypeChanges['entity_type'], $entityTypeId);
            }

            // Process field storage definition changes.
            if (!empty($entityTypeChanges['field_storage_definitions'])) {
                $storageDefinitions = $this->entityFieldManager->getFieldStorageDefinitions($entityTypeId);
                $originalStorageDefinitions = $this->entityLastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($entityTypeId);

                foreach ($entityTypeChanges['field_storage_definitions'] as $fieldName => $change) {
                    $storageDefinition = $storageDefinitions[$fieldName] ?? NULL;
                    $originalStorageDefinition = $originalStorageDefinitions[$fieldName] ?? NULL;
                    $this->doFieldUpdate($change, $storageDefinition, $originalStorageDefinition);
                }
            }
        }
    }

    protected function doEntityUpdate(string $op, string $entityTypeId): void
    {
        $entityType = $this->entityTypeManager->getDefinition($entityTypeId);

        switch ($op) {
            case EntityDefinitionUpdateManagerInterface::DEFINITION_CREATED:
                $this->entityTypeListener->onEntityTypeCreate($entityType);
                break;

            case EntityDefinitionUpdateManagerInterface::DEFINITION_UPDATED:
                $original = $this->entityLastInstalledSchemaRepository->getLastInstalledDefinition($entityTypeId);
                $storage = $this->entityTypeManager->getStorage($entityType->id());
                if ($storage instanceof EntityStorageSchemaInterface && $storage->requiresEntityDataMigration($entityType, $original)) {
                    throw new \InvalidArgumentException("The entity schema update for the {$entityType->id()} entity type requires a data migration.");
                }
                $fieldStorageDefinitions = $this->entityFieldManager->getFieldStorageDefinitions($entityTypeId);
                $originalFieldStorageDefinitions = $this->entityLastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($entityTypeId);
                $this->entityTypeListener->onFieldableEntityTypeUpdate($entityType, $original, $fieldStorageDefinitions, $originalFieldStorageDefinitions);
                break;
        }
    }

    private function doFieldUpdate(string $op, FieldStorageDefinitionInterface $storageDefinition = null, FieldStorageDefinitionInterface $originalStorageDefinition = null): void
    {
        switch ($op) {
            case EntityDefinitionUpdateManagerInterface::DEFINITION_CREATED:
                $this->fieldStorageDefinitionListener->onFieldStorageDefinitionCreate($storageDefinition);
                break;

            case EntityDefinitionUpdateManagerInterface::DEFINITION_UPDATED:
                if ($storageDefinition && $originalStorageDefinition) {
                    $this->fieldStorageDefinitionListener->onFieldStorageDefinitionUpdate($storageDefinition, $originalStorageDefinition);
                }
                break;

            case EntityDefinitionUpdateManagerInterface::DEFINITION_DELETED:
                if ($originalStorageDefinition) {
                    $this->fieldStorageDefinitionListener->onFieldStorageDefinitionDelete($originalStorageDefinition);
                }
                break;
        }
    }
}
