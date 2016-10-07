<?php

namespace Drupal\wmcontent\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityLink;

/**
 * Provides a wmcontent link for an entity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("wmcontent_delete_link")
 */
class WmContentDelete extends EntityLink {

  /**
   * {@inheritdoc}
   */
  protected function getEntityLinkTemplate() {
    return 'drupal:wmcontent-delete';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Delete WmContent');
  }

}
