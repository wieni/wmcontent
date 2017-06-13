<?php

namespace Drupal\wmcontent;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\wmcontent\Entity\WmContentContainer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;

class WmContentManager
{
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface */
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

    /**
     * {@inheritdoc}
     */
    public function getContent(ContentEntityInterface $host, $container)
    {
        $data = &drupal_static(__FUNCTION__);

        if (!$container instanceof WmContentContainer) {
            $container = $this->getContainer($container);
        }

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

        // Query database
        $data[$key] = $storage
            ->getQuery()
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

    public function getHost(ContentEntityInterface $contentBlock)
    {
        if (!$this->isContentBlock($contentBlock)) {
            return null;
        }

        return $this
            ->entityTypeManager
            ->getStorage($contentBlock->get('wmcontent_parent_type')->value)
            ->load($contentBlock->get('wmcontent_parent')->value);
    }

    public function isContentBlock(ContentEntityInterface $contentBlock)
    {
        return $contentBlock->hasField('wmcontent_parent') && !$contentBlock->get('wmcontent_parent')->isEmpty();
    }

    /** @return WmContentContainer[] */
    public function getHostContainers(ContentEntityInterface $host)
    {
        return array_filter(
            $this->getContainers(),
            function (WmContentContainer $container) use ($host) {
                return $container->isHost($host);
            }
        );
    }

    /** @return WmContentContainer[] */
    public function getContainers(ContentEntityInterface $contentBlock = null)
    {
        $containers = $this
            ->entityTypeManager
            ->getStorage('wmcontent_container')
            ->loadMultiple();

        if (!$contentBlock) {
            return $containers;
        }

        return array_filter(
            $containers,
            function (WmContentContainer $container) use ($contentBlock) {
                return $container->hasContentBlock($contentBlock);
            }
        );
    }

    {
        $this->eventDispatcher->dispatch(
    }

    /** @return \Drupal\wmcontent\Entity\WmContentContainer */
    private function getContainer($containerName)
    {
        $container = $this
            ->entityTypeManager
            ->getStorage('wmcontent_container')
            ->load($containerName);

        if (!$container) {
            throw new \Exception(sprintf(
                'Could not find wmcontent container `%s`',
                $containerName
            ));
        }

        return $container;
    }
}
