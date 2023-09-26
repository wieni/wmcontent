<?php

namespace Drupal\wmcontent\Entity;

use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;
use Drupal\wmcontent\Entity\Traits\BaseFieldTrait;
use Drupal\wmcontent\WmContentContainerInterface;

/**
 * @ContentEntityType(
 *     id = "wmcontent_snapshot",
 *     label = @Translation("WmContent Snapshot"),
 *     handlers = {
 *         "list_builder" : "Drupal\wmcontent\Service\Snapshot\SnapshotListBuilder",
 *         "form" : {
 *             "default" : "Drupal\Core\Entity\ContentEntityForm",
 *             "add" : "Drupal\Core\Entity\ContentEntityForm",
 *             "edit" : "\Drupal\wmcontent\Form\Snapshot\SnapshotEntityForm",
 *             "delete" : "Drupal\Core\Entity\ContentEntityDeleteForm",
 *         },
 *         "route_provider" : {
 *             "html" : "Drupal\wmcontent\Service\Snapshot\HtmlRouteProvider",
 *         },
 *         "access" : "Drupal\Core\Entity\EntityAccessControlHandler",
 *     },
 *     base_table = "wmcontent_snapshot",
 *     translatable = FALSE,
 *     admin_permission = "administer wmcontent",
 *     entity_keys = {
 *         "id" : "id",
 *         "label" : "title",
 *         "uuid" : "uuid",
 *         "langcode" : "langcode",
 *     },
 *     links = {
 *         "add-form" : "/admin/wmcontent/snapshot/add",
 *         "edit-form" : "/admin/wmcontent/snapshot/{wmcontent_snapshot}/edit",
 *         "delete-form" : "/admin/wmcontent/snapshot/{wmcontent_snapshot}/delete",
 *         "collection" : "/admin/wmcontent/snapshot",
 *     },
 *     field_ui_base_route = "entity.wmcontent_snapshot.collection"
 * )
 */
class Snapshot extends ContentEntityBase
{
    use BaseFieldTrait;

    public function label(): string
    {
        return $this->getTitle();
    }

    public function getTitle(): string
    {
        return (string) $this->get('title')->value;
    }

    public function getComment(): string
    {
        return (string) $this->get('comment')->value;
    }

    public function getSourceLangcode(): string
    {
        return (string) $this->get('source_langcode')->value;
    }

    public function getOwner(): ?UserInterface
    {
        return $this->get('user_id')->entity;
    }

    public function isActive(): bool
    {
        return (bool) $this->get('active')->value;
    }

    public function getContainer(): ?WmContentContainerInterface
    {
        return $this->get('wmcontent_container')->entity;
    }

    public function getHost(): ?EntityInterface
    {
        // These properties should be renamed to 'host_entity_type' and
        // 'host_entity_id'. I hope to fix this in a future major.
        $entityType = (string) $this->get('source_entity_type')->value;
        $entityId = (string) $this->get('source_entity_type')->value;

        // Don't use 'source_langcode', it's the langcode of the initial
        // snapshots' host. Not the langcode of the current host. Sorry for the
        // confusion.
        $langcode = $this->language()->getId();

        if (empty($entityType) || empty($entityId)) {
            return null;
        }

        $host = $this->entityTypeManager()
            ->getStorage($entityType)
            ->load($entityId);

        if (
            !empty($langcode)
            && $host instanceof TranslatableInterface
            && $host->isTranslatable()
            && $host->hasTranslation($langcode)
        ) {
            $host = $host->getTranslation($langcode);
        }

        return $host;
    }

    public static function baseFieldDefinitions(EntityTypeInterface $entityType): array
    {
        $fields = parent::baseFieldDefinitions($entityType);

        $fields['title'] = static::getStringBaseFieldDefinition(true)
            ->setRequired(true)
            ->setLabel(t('Title'));

        $fields['environment'] = static::getStringBaseFieldDefinition(true)
            ->setRequired(true)
            ->setLabel(t('Environment'))
            ->setDisplayConfigurable('form', false);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setRequired(true)
            ->setLabel(t('Created'))
            ->setDisplayConfigurable('form', false);

        $fields['comment'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Comment'))
            ->setDisplayConfigurable('form', true)
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
            ]);

        $fields['blob'] = BaseFieldDefinition::create('string_long')
            ->setRequired(true)
            ->setLabel(t('Comment'))
            ->setDisplayConfigurable('form', false)
            ->setSetting('case_sensitive', true); // so it's stored as blob

        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setRequired(true)
            ->setLabel(t('User ID'))
            ->setDescription(t('The ID of the associated user.'))
            ->setSetting('target_type', 'user')
            ->setSetting('handler', 'default')
            ->setDisplayConfigurable('form', false);

        $fields['source_langcode'] = static::getStringBaseFieldDefinition(true)
            ->setRequired(true)
            ->setLabel(t('Source langcode'))
            ->setDisplayConfigurable('form', false);

        // Todo: Rename this to 'host_entity_type'
        $fields['source_entity_type'] = static::getStringBaseFieldDefinition(true)
            ->setRequired(false) // todo: if null this snapshot can be applied to any host
            ->setLabel(t('Source entity type'))
            ->setDisplayConfigurable('form', false);

        // Todo: Rename this to 'host_entity_id'
        $fields['source_entity_id'] = static::getIntegerBaseFieldDefinition(true)
            ->setRequired(false) // todo: if null this snapshot can be applied to any host
            ->setLabel(t('Source entity id'))
            ->setDisplayConfigurable('form', false);

        $fields['wmcontent_container'] = BaseFieldDefinition::create('entity_reference')
            ->setRequired(false) // todo: if null this snapshot can be applied to any container
            ->setSetting('target_type', 'wmcontent_container')
            ->setSetting('handler', 'default')
            ->setDisplayConfigurable('form', false);

        $fields['active'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('active'))
            ->setDisplayConfigurable('form', false);

        return $fields;
    }

    public function getCreatedTime(): int
    {
        return (int) $this->get('created')->value;
    }

    public function getBlob(): array
    {
        $blob = (string) $this->get('blob')->value;
        if (empty($blob)) {
            return [];
        }
        return json_decode(
            $blob,
            true,
            512 // depth - default value
        );
    }

    public function getEnvironment(): string
    {
        return (string) $this->get('environment')->value;
    }

    public function toArray(): array
    {
        return [
            'title' => $this->getTitle(),
            'comment' => $this->getComment(),
            'environment' => $this->getEnvironment(),
            'created' => $this->getCreatedTime(),
            'blob' => $this->getBlob(),
            'user_id' => $this->getOwner()
                ? $this->getOwner()->id()
                : 0,
            'source_langcode' => $this->get('source_langcode')->value,
            'source_entity_type' => $this->get('source_entity_type')->value,
            'source_entity_id' => $this->get('source_entity_id')->value,
            'wmcontent_container' => $this->getContainer()
                ? $this->getContainer()->id()
                : null,
            'active' => $this->isActive(),
        ];
    }

    public static function fromArray(array $data, string $langcode): Snapshot
    {
        /** @var static $snapshot */
        $snapshot = static::create([
            'langcode' => $langcode,
        ]);

        $snapshot->set('title', $data['title'] ?? '');
        $snapshot->set('comment', $data['comment'] ?? '');
        $snapshot->set('environment', $data['environment'] ?? '');
        if (!empty($data['created'])) {
            $snapshot->set('created', $data['created']);
        }
        $snapshot->set('wmcontent_container', $data['wmcontent_container'] ?? null);
        $snapshot->set('user_id', $data['user_id'] ?? null);
        $snapshot->set('source_langcode', $data['source_langcode'] ?? $langcode);
        $snapshot->set('source_entity_type', $data['source_entity_type'] ?? '');
        $snapshot->set('source_entity_id', $data['source_entity_id'] ?? '');
        $snapshot->set('active', 0);
        $snapshot->set('blob', json_encode(
            $data['blob']
        ));

        return $snapshot;
    }

    public function setHost(EntityInterface $host)
    {
        // Don't set 'source_langcode', that's the langcode of the initial
        // snapshots' host. When we import an 'nl' snapshot to an 'fr' host
        // 'source_langcode' stays 'nl' and 'langcode' is 'fr'.
        //
        // These properties should be renamed to 'host_entity_type' and
        // 'host_entity_id'. I hope to fix this in a future major.
        $this->set('source_entity_type', $host->getEntityTypeId());
        $this->set('source_entity_id', $host->id());
        return $this;
    }

    public function setContainer(WmContentContainerInterface $container)
    {
        $this->set('wmcontent_container', $container->id());
        return $this;
    }
}
