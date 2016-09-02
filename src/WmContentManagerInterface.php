<?php

namespace Drupal\wmcontent;

/**
 * Provides an interface for common functionality for wmcontent.
 */
interface WmContentManagerInterface {

  /**
   * Gets all the entities that links to this entity.
   *
   * @return array
   *   List of content entities.
   */
  public function getContent($entity, $container);

  /**
   * Gets the containers for a host entity.
   *
   * @return array
   *   List of content entities.
   */
  public function getHostContainers($host);

  /**
   * Get current language.
   */
  public function getCurrentLanguage();

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
   * Function getEntityTeaser.
   *
   * Returns hopefully a little teaser of a pargraph that is given.
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
