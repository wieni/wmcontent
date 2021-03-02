<?php

namespace Drupal\wmcontent\Service\Snapshot;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Link;
use Drupal\wmcontent\Form\Snapshot\ModalAjaxProxyTrait;
use Drupal\wmcontent\Form\Snapshot\SnapshotFormBase;
use Drupal\wmcontent\WmContentContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SnapshotListBuilder extends EntityListBuilder implements SnapshotListBuilderInterface
{
    use ModalAjaxProxyTrait;

    /** @var \Drupal\Core\Language\LanguageManagerInterface */
    protected $languageManager;
    /** @var \Drupal\Core\Form\FormBuilderInterface */
    protected $formBuilder;

    /** @var WmContentContainerInterface|null */
    protected $contentContainer;
    /** @var EntityInterface|null */
    protected $host;

    protected $limit = 10;

    public static function createInstance(ContainerInterface $container, EntityTypeInterface $entityType)
    {
        /** @var static $listBuilder */
        $listBuilder = parent::createInstance($container, $entityType);
        $listBuilder->languageManager = $container->get('language_manager');
        $listBuilder->formBuilder = $container->get('form_builder');
        return $listBuilder;
    }

    public function setContainer(?WmContentContainerInterface $container): void
    {
        $this->contentContainer = $container;
    }

    public function setHost(?EntityInterface $host): void
    {
        $this->host = $host;
    }

    public function render()
    {
        $overview = [];
        if ($this->isAjax()) {
            // In a modal Drupal doesn't load the usual blocks. So we add our own
            SnapshotFormBase::addModalDrupalBlocks($overview);
        }

        $overview += parent::render();
        $overview['table']['#empty'] = $this->t('There are no snapshots yet');
        return $overview;
    }

    public function buildHeader(): array
    {
        $header['name'] = $this->t('Title');
        $header['created'] = $this->t('Date created');
        $header['user'] = $this->t('Created by');
        $header['comment'] = $this->t('Description');
        if (!$this->host) {
            $header['parent'] = $this->t('Parent');
        }
        return $header + parent::buildHeader();
    }

    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\wmcontent\Entity\Snapshot $entity*/
        $row['name'] = [
            'data' => [
                '#markup' => $entity->label(),
            ],
        ];
        $row['created'] = [
            'data' => [
                '#markup' => date('d/m/Y H:i', $entity->getCreatedTime()),
            ],
        ];
        $row['user'] = [
            'data' => [
                '#markup' => $entity->getOwner()
                    ? $entity->getOwner()->label()
                    : '',
            ],
        ];
        $row['comment'] = [
            'data' => [
                '#markup' => $entity->getComment(),
            ],
        ];
        if (!$this->host) {
            $row['parent'] = Link::createFromRoute(
                $entity->getHost()->label(),
                'entity.wmcontent_snapshot.edit_form',
                ['wmcontent_snapshot' => $entity->id()]
            );
        }
        return $row + parent::buildRow($entity);
    }

    protected function getEntityIds()
    {
        $q = $this->getStorage()->getQuery();

        $q->condition('langcode', $this->getLangcode());

        if ($this->contentContainer) {
            $q->condition('wmcontent_container', $this->contentContainer->id());
        }

        if ($this->host) {
            $q->condition($or = $q->orConditionGroup());

            // Any snapshot without host ( for example a template )
            $or->condition(
                $q->andConditionGroup()
                    ->notExists('source_entity_type')
                    ->notExists('source_entity_id')
            );
            // Any snapshot of this host
            $or->condition(
                $q->andConditionGroup()
                    ->condition('source_entity_type', $this->host->getEntityTypeId())
                    ->condition('source_entity_id', $this->host->id())
            );
        }

        $q->sort('id', 'DESC');

        if ($this->limit) {
            $q->pager($this->limit);
        }

        return $q->execute();
    }

    protected function getLangcode(): string
    {
        return $this->host
            ? $this->host->language()->getId()
            : $this->languageManager->getCurrentLanguage()->getId();
    }
}
