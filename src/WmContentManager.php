<?php

namespace Drupal\wmcontent;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityInterface;
use Drupal\eck\EckEntityInterface;
use Drupal\eck\Entity\EckEntity;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\wmcontent\Event\WmContentEntityLabelEvent;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Provides common functionality for content translation.
 */
class WmContentManager implements WmContentManagerInterface
{

    /**
     * The entity manager.
     *
     * @var \Drupal\Core\Entity\EntityManagerInterface
     */
    protected $entityManager;

    /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

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
     * Constructs a WmContentManageAccessCheck object.
     *
     * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     * @param \Drupal\Core\Entity\Query\QueryFactory|\Drupal\Core\Entity\QueryFactory $query
     *   The query factory.
     * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
     *   The language manager.
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
     *   The event dispatcher.The entity type manager.
     * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        EntityTypeManagerInterface $entityTypeManager,
        QueryFactory $query,
        LanguageManagerInterface $language_manager,
        EventDispatcherInterface $event_dispatcher,
        CacheBackendInterface $cacheBackend
    ) {
        $this->entityManager = $entityManager;
        $this->entityTypeManager = $entityTypeManager;
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
        return $this->entityManager->getHandler($entity_type_id, 'translation');
    }

    /**
     * {@inheritdoc}
     */
    public function getContent($entity, $container)
    {
        $data = &drupal_static(__FUNCTION__);
        $key = 'wmcontent:' . $container . ':' . $entity->getEntityTypeId() . ':' . $entity->id() . ':' . $entity->get('langcode')->value;
    
        // Load the container.
        $current_container = $this
            ->entityManager
            ->getStorage('wmcontent_container')
            ->load($container);

        if (!$current_container) {
            throw new \Exception(sprintf(
                'Could not find wmcontent container `%s`',
                $container
            ));
        }
        
        if (!isset($data[$key])) {
            if ($cache = $this->cacheBackend->get($key)) {
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

        $controller = $this->entityManager->getStorage($current_container->getChildEntityType());
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
                ->entityManager
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

        $trigger = $host->getEntityTypeId() . "|" . $host->bundle();

        // Holder.
        $return = [];

        // Go through all containers.
        foreach ($containers as $container) {
            // Look at the host bundles.
            $typebundles = $container->host->host_typebundles;

            // Go through all of them and if we are there give back that.
            foreach ($typebundles as $typebundle) {
                if ($typebundle == $trigger) {
                    $return[] = $container;
                }
            }
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
            $labels[$entityType] = $this->entityManager->getBundleInfo($entityType);
        }
        
        if (!empty($labels[$entityType][$bundle]['label'])) {
            return $labels[$entityType][$bundle]['label'];
        }
        return $bundle;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityTeaser(EntityInterface $entity)
    {
        // Allow overrides through event dispatching.
        $event = new WmContentEntityLabelEvent($entity);

        // Return the event results.
        $this
            ->eventDispatcher
            ->dispatch('wmcontent.entitylabel', $event);
        if ($event->getLabel()) {
            $return = $event->getLabel();
        }

        // If we got to here do the title.
        $title = "ID: " . $entity->id();
        try {
            $title = $entity->get('title')->value;
        } catch (\InvalidArgumentException $exception) {
            // Do nothing.
        }

        return $title;
    }

    /**
     * {@inheritdoc}
     */
    public function hostClearCache($child_entity)
    {
        // Ideally we should add cache tags to our content, then this won't be
        // necesarry. If it works at all.
        $host_type = $child_entity->get('wmcontent_parent_type')->getString();
        $host_id = $child_entity->get('wmcontent_parent')->getString();

        // Load the master entity.
        $host_entity = $this
            ->entityManager
            ->getStorage($host_type)
            ->load($host_id);
        if ($host_entity) {
            $this
                ->entityManager
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
            ->entityManager
            ->getStorage('wmcontent_container')
            ->loadMultiple();
    }
}
