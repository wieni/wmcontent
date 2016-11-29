<?php

namespace Drupal\wmcontent;

use Drupal\eck\Entity\EckEntity;

/**
 * Provides an interface for common functionality for wmcontent.
 */
interface WmContentManagerInterface
{
    
    /**
     * Gets all the entities that links to this entity.
     *
     * @return array
     *   List of content entities.
     */
    public function getContent($entity, $container);

    /**
     * Gets all the TOC that links to this entity.
     *
     * @return array
     *   Table of contents array.
     */
    public function getToc($entity, $container);
    
    /**
     * Get the host of an entity.
     *
     * @param $entity
     * @return mixed
     */
    public function getHost(EckEntity $entity);
    
    /**
     * Gets the containers for a host entity.
     *
     * @return array
     *   List of content entities.
     */
    public function getHostContainers($host);
    
    /**
     * Returns an instance of the Content translation handler.
     *
     * @param string $entity_type_id
     *   The type of the entity being translated.
     *
     * @return \Drupal\content_translation\ContentTranslationHandlerInterface
     *   An instance of the content translation handler.
     */
    public function getTranslationHandler($entity_type_id);
    
    /**
     * Returns the label of a bundle, given the key
     *
     * @param $entityType
     *   The entity type of the bundle.
     *
     * @param $bundle
     *   The bundle key
     *
     * @return string
     *   The label of that bundle.
     */
    public function getLabel($entityType, $bundle);
    
    /**
     * Function getEntityTeaser.
     *
     * Returns hopefully a little teaser of a paragraph that is given.
     *
     * @param mixed $entity
     *   The paragraph that you want to try.
     *
     * @return string
     *   A small string that should help the user know what this is.
     */
    public function getEntityTeaser($entity);
    
    /**
     * Clears caches for the host, so that adding wmcontent shows always the right
     * thing.
     */
    public function hostClearCache($child_entity);
}
