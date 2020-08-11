<?php

namespace Drupal\wmcontent\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;
use Drupal\wmcontent\Entity\Traits\BaseFieldTrait;

/**
 * @ContentEntityType(
 *   id = "wmcontent_snapshot_log",
 *   label = @Translation("WmContent Snapshot log"),
 *   handlers = {
 *     "list_builder" = "\Drupal\Core\Entity\EntityListBuilder",
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *   },
 *   base_table = "wmcontent_snapshot_log",
 *   translatable = FALSE,
 *   admin_permission = "administer wmcontent",
 *   entity_keys = {
 *     "id" = "id",
 *   }
 * )
 */
class SnapshotLog extends ContentEntityBase
{
    use BaseFieldTrait;

    public function label()
    {
        return Unicode::truncate(
            $this->getComment(),
            75,
            false,
            true
        );
    }

    public function getComment()
    {
        return (string) $this->get('comment')->value;
    }

    public function getOwner(): ?UserInterface
    {
        return $this->get('user_id')->entity;
    }

    public function getSnapshot(): ?Snapshot
    {
        return $this->get('snapshot')->entity;
    }

    public function getHost(): ?EntityInterface
    {
        $entityType = (string) $this->get('source_entity_type')->value;
        $entityId = (string) $this->get('source_entity_id')->value;

        if (empty($entityType) || empty($entityId)) {
            return null;
        }

        return $this->entityTypeManager()
            ->getStorage($entityType)
            ->load($entityId);
    }

    public static function baseFieldDefinitions(EntityTypeInterface $entityType)
    {
        $fields = parent::baseFieldDefinitions($entityType);

        $fields['snapshot'] = static::getStringBaseFieldDefinition(true)
            ->setLabel(t('Title'));

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'));

        $fields['comment'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Comment'))
            ->setDisplayConfigurable('form', true)
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
            ]);

        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('User ID'))
            ->setDescription(t('The ID of the associated user.'))
            ->setSetting('target_type', 'user')
            ->setSetting('handler', 'default');

        $fields['source_entity_type'] = static::getStringBaseFieldDefinition(true)
            ->setLabel(t('Source entity type'));

        $fields['source_entity_id'] = static::getIntegerBaseFieldDefinition(true)
            ->setLabel(t('Source entity id'));

        return $fields;
    }
}
