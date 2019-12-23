<?php

namespace Drupal\wmcontent;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WmContentManager implements WmContentManagerInterface
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;
    /** @var CacheBackendInterface */
    protected $cacheBackend;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        EventDispatcherInterface $event_dispatcher,
        CacheBackendInterface $cacheBackend
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->eventDispatcher = $event_dispatcher;
        $this->cacheBackend = $cacheBackend;
    }

    public function getContent(EntityInterface $host, string $containerId): array
    {
        $data = &drupal_static(__FUNCTION__);
        $container = $this->getContainer($containerId);

        $storage = $this->entityTypeManager->getStorage($container->getChildEntityType());
        $key = 'wmcontent:' . $container->id() . ':' . $host->getEntityTypeId() . ':' . $host->id() . ':' . $host->get('langcode')->value;

        // Return statically cached data
        if (isset($data[$key])) {
            return $storage->loadMultiple($data[$key]);
        }

        // Return database-cached data
        if ($cache = $this->cacheBackend->get($key)) {
            $data[$key] = $cache->data;
            return $storage->loadMultiple($data[$key]);
        }

        $data[$key] = $storage->getQuery()
            ->condition('wmcontent_parent', $host->id())
            ->condition('wmcontent_parent_type', $host->getEntityTypeId())
            ->condition('langcode', $host->language()->getId())
            ->condition('wmcontent_container', $container->id())
            ->sort('wmcontent_weight', 'ASC')
            ->execute();

        // Put in cache. Mind the invalidating array that should invalidate
        // this cache when the node gets cleared.
        $this->cacheBackend->set(
            $key,
            $data[$key],
            CacheBackendInterface::CACHE_PERMANENT,
            $host->getCacheTags()
        );

        return $storage->loadMultiple($data[$key]);
    }

    public function getHost(EntityInterface $child): EntityInterface
    {
        if (!$this->isChild($child)) {
            return null;
        }

        $langcode = $child->language()->getId();
        $entity = $this->entityTypeManager
            ->getStorage($child->get('wmcontent_parent_type')->value)
            ->load($child->get('wmcontent_parent')->value);

        if (!$entity instanceof EntityInterface) {
            return null;
        }

        if ($entity->language()->getId() === $langcode) {
            return $entity;
        }

        if ($entity instanceof TranslatableInterface && $entity->hasTranslation($langcode)) {
            return $entity->getTranslation($langcode);
        }

        return null;
    }

    public function isChild(EntityInterface $child): bool
    {
        return $child->hasField('wmcontent_parent')
            && !$child->get('wmcontent_parent')->isEmpty();
    }

    public function getContainers(): array
    {
        return $this->entityTypeManager
            ->getStorage('wmcontent_container')
            ->loadMultiple();
    }

    public function getHostContainers(EntityInterface $host): array
    {
        return array_filter(
            $this->getContainers(),
            static function (WmContentContainerInterface $container) use ($host) {
                return $container->isHost($host);
            }
        );
    }

    public function getChildContainers(EntityInterface $child): array
    {
        return array_filter(
            $this->getContainers(),
            static function (WmContentContainerInterface $container) use ($child) {
                return $container->hasChild($child);
            }
        );
    }

    protected function getContainer(string $id): WmContentContainerInterface
    {
        $container = $this->entityTypeManager
            ->getStorage('wmcontent_container')
            ->load($id);

        if (!$container) {
            throw new \RuntimeException(sprintf(
                'Could not find wmcontent container `%s`',
                $id
            ));
        }

        return $container;
    }
}
