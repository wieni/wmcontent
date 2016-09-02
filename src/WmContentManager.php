<?php

namespace Drupal\wmcontent;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\wmcontent\Event\WmContentEntityLabelEvent;

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
     * Constructs a WmContentManageAccessCheck object.
     *
     * @param \Drupal\Core\Entity\EntityManagerInterface $manager
     *   The entity type manager.
     * @param \Drupal\Core\Entity\QueryFactory $query
     *   The query factory.
     * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
     *   The language manager.
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
     *   The event dispatcher.
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        EntityTypeManagerInterface $entityTypeManager,
        QueryFactory $query,
        LanguageManagerInterface $language_manager,
        EventDispatcherInterface $event_dispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->entityTypeManager = $entityTypeManager;
        $this->entityQuery = $query;
        $this->languageManager = $language_manager;
        $this->eventDispatcher = $event_dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentLanguage()
    {
        return $this->languageManager->getCurrentLanguage();
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

        // Load the container.
        $current_container = $this
        ->entityManager
        ->getStorage('wmcontent_container')
        ->load($container);

        // Create an entity query for our entity type.
        $query = $this->entityQuery->get($current_container->getChildEntityType());

        // Get the current host language.
        $langcode = $this->getCurrentLanguage()->getId();

        // Filter by parent and sort.
        $query
        ->condition('wmcontent_parent', $entity->id())
        ->condition('wmcontent_parent_type', $entity->getEntityTypeId())
        ->condition('langcode', $langcode)
        ->condition('wmcontent_container', $container)
        ->sort('wmcontent_weight', 'ASC');

        // Return the entities.
        $result = $query->execute();

        $controller = $this->entityManager->getStorage($current_container->getChildEntityType());
        return $controller->loadMultiple($result);
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
    public function getEntityTeaser($entity)
    {

        // Do not consider these fields when making a teaser.
        $ignorefields = [
        'id',
        'uuid',
        'type',
        'langcode',
        'created',
        'changed',
        'default_langcode',
        'wmcontent_size',
        'wmcontent_weight',
        'wmcontent_parent',
        'wmcontent_parent_type',
        'wmcontent_container',
        'weight',
        'content_translation_source',
        'content_translation_outdated',
        'content_translation_uid',
        'content_translation_status',
        'content_translation_changed',
        'list_items',
        'program_items',
        ];

        // Setup a priority for key indexes.
        $trickleindexes = [
        'value',
        'title',
        ];

        // Get the type (paragraph).
        $entity_type_id = $entity->getEntityTypeId();
        // Get the bundle.
        $bundle = $entity->bundle();

        // Get the fields.
        $fields = $this->entityManager->getFieldDefinitions($entity_type_id, $bundle);
        $return = false;
        // Loop through the fields.
        foreach ($fields as $field_name => $field_definition) {
            // Is this a non standard field?
            if (!in_array($field_name, $ignorefields)) {
                // Get the container array for the value.
                $value = $entity->get($field_name)->getValue();

                if (is_array($value)) {
                  // Go through the value, find a trickle index.
                    foreach ($trickleindexes as $i) {
                        if (isset($value[0]) && isset($value[0][$i]) && !empty($value[0][$i])) {
                            $value = substr(trim(strip_tags($value[0][$i])), 0, 60);
                            if (!is_numeric($value)) {
                              // Good to go.
                                $return = $value;
                            }
                        }
                    }
                }

                if (!$return) {
                  // OK There was nothing in the value that was interesting.
                  // So let's try the string.
                    $value = $entity->get($field_name)->getString();

                  // See if the string value has something nice.
                    if (!empty($value)) {
                        $value = substr(trim(strip_tags($value)), 0, 60);
                        if (!is_numeric($value)) {
                          // Good to go.
                            $return = $value;
                        }
                    }
                }
            }
        }


        // Allow overrides through event dispatching.
        $event = new WmContentEntityLabelEvent($entity);

        $this
            ->eventDispatcher
            ->dispatch('wmcontent.entitylabel', $event);
        if ($event->getLabel()) {
            $return = $event->getLabel();
        }

        // If we got to here just return the id().
        if (!$return) {
            $return = "ID: " . $entity->id();
        }

        return $return;
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
}
