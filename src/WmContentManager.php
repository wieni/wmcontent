<?php

namespace Drupal\wmcontent;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\eck\Entity\EckEntity;
use Drupal\wmcontent\Entity\WmContentContainer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\wmcontent\Event\WmContentEntityLabelEvent;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Provides common functionality for content translation.
 */
class WmContentManager implements WmContentManagerInterface
{

    /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;


    /** @var EntityTypeBundleInfo */
    protected $entityTypeBundleInfo;

    /**
     * The query interface.
     */
    protected $entityQuery;

    /**
     * The language manager.
     */
    protected $languageManager;

    /**
     * Event dispatcher manager.
     *
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var CacheBackendInterface
     */
    protected $cacheBackend;


    /**
     * WmContentManager constructor.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     * @param \Drupal\Core\Entity\EntityTypeBundleInfo $entityTypeBundleInfo
     * @param \Drupal\Core\Entity\Query\QueryFactory $query
     * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
     * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        EntityTypeBundleInfo $entityTypeBundleInfo,
        QueryFactory $query,
        LanguageManagerInterface $language_manager,
        EventDispatcherInterface $event_dispatcher,
        CacheBackendInterface $cacheBackend
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->entityTypeBundleInfo = $entityTypeBundleInfo;
        $this->entityQuery = $query;
        $this->languageManager = $language_manager;
        $this->eventDispatcher = $event_dispatcher;
        $this->cacheBackend = $cacheBackend;
    }

    /**
     * {@inheritdoc}
     */
    public function getTranslationHandler($entity_type_id)
    {
        return $this->entityTypeManager->getHandler($entity_type_id, 'translation');
    }

    /**
     * {@inheritdoc}
     */
    public function getContent(EntityInterface $entity, $container)
    {
        $data = &drupal_static(__FUNCTION__);
        $key = 'wmcontent:' . $container . ':' . $entity->getEntityTypeId() . ':' . $entity->id() . ':' . $entity->get('langcode')->value;

        // Load the container.
        /** @var WmContentContainer $current_container */
        $current_container = $this
            ->entityTypeManager
            ->getStorage('wmcontent_container')
            ->load($container);

        if (!$current_container) {
            throw new \Exception(sprintf(
                'Could not find wmcontent container `%s`',
                $container
            ));
        }

        if (!isset($data[$key])) {
            $cache = $this->cacheBackend->get($key);
            if ($cache) {
                $data[$key] = $cache->data;
            } else {
                // Create an entity query for our entity type.
                $query = $this->entityQuery->get($current_container->getChildEntityType());

                // Filter by parent and sort.
                $query
                    ->condition('wmcontent_parent', $entity->id())
                    ->condition('wmcontent_parent_type', $entity->getEntityTypeId())
                    ->condition('langcode', $entity->get('langcode')->value)
                    ->condition('wmcontent_container', $container)
                    ->sort('wmcontent_weight', 'ASC');

                // Return the entities.
                $data[$key] = $query->execute();

                // Put in cache. Mind the invalidating array that should invalidate
                // this cache when the node gets cleared.
                $this->cacheBackend->set(
                    $key,
                    $data[$key],
                    CacheBackendInterface::CACHE_PERMANENT,
                    $entity->getCacheTags()
                );
            }
        }

        $controller = $this->entityTypeManager->getStorage($current_container->getChildEntityType());

        return $controller->loadMultiple($data[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function getToc($entity, $container)
    {
        // Start a return.
        $r = [];

        // Get the kids.
        $kids = $this->getContent($entity, $container);

        // Start a counter.
        $i = 1;

        // Loop through the kids.
        foreach ($kids as $kid) {
            // If this kid is a title then ...
            if ($kid->bundle() == "title") {
                // get the title.
                $t = $kid->get('plain_title')->value;
                // Just put the id number of the thing there....
                $id = "entity-" . $kid->id();

                // Add this title to the TOC
                $r[] = [
                    'label' => $t,
                    'href' => "#" . $id,
                ];
            }
            $i++;
        }

        return $r;
    }

    /**
     * {@inheritdoc}
     */
    public function getHost(EckEntity $entity)
    {
        if ($entity->hasField('wmcontent_parent') && !$entity->get('wmcontent_parent')->isEmpty()) {
            return $this
                ->entityTypeManager
                ->getStorage($entity->get('wmcontent_parent_type')->value)
                ->load($entity->get('wmcontent_parent')->value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getHostContainers($host)
    {
        $containers = $this->getContainers();
        $return = [];
        foreach ($containers as $container) {
            if (!$container->isHost($host)) {
                continue;
            }
            $return[] = $container->getId();
        }
        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel($entityType, $bundle)
    {
        $labels = &drupal_static(__FUNCTION__);
        if (!isset($labels[$entityType])) {
            $labels[$entityType] = $this->entityTypeBundleInfo->getBundleInfo($entityType);
        }

        if (!empty($labels[$entityType][$bundle]['label'])) {
            return $labels[$entityType][$bundle]['label'];
        }
        return ucwords(str_replace("_", " ", $bundle));
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityTeaser(EntityInterface $entity)
    {
        // Allow overrides through event dispatching.
        $event = new WmContentEntityLabelEvent($entity);

        $return = $entity->label() ?: "ID: $entity->id()";
        // Event allow override.
        $this->eventDispatcher->dispatch('wmcontent.entitylabel', $event);
        if ($event->getLabel()) {
            $return = $event->getLabel();
        }

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function hostClearCache($child_entity)
    {
        $host_entity = $this->getHost($child_entity);
        if ($host_entity) {
            $this
                ->entityTypeManager
                ->getViewBuilder($child_entity->get('wmcontent_parent_type')->getString())
                ->resetCache([
                    $host_entity,
                ]);
        }
    }

    /**
     * Get all containers
     *
     * @return WmContentContainerInterface[]
     */
    private function getContainers()
    {
        return $this
            ->entityTypeManager
            ->getStorage('wmcontent_container')
            ->loadMultiple();
    }
}
