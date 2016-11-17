<?php

namespace Drupal\wmcontent\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityLink;

/**
 * Provides a wmcontent link for an entity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("wmcontent_add_link")
 */
class WmContentAdd extends EntityLink {

  /**
   * {@inheritdoc}
   */
  protected function getEntityLinkTemplate() {
    return 'drupal:wmcontent-add';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Add WmContent');
  }

}
