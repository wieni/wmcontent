<?php

namespace Drupal\wmcontent\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityLink;

/**
 * @ViewsField("wmcontent_delete_link")
 */
class WmContentDelete extends EntityLink
{
    protected function getEntityLinkTemplate()
    {
        return 'drupal:wmcontent-delete';
    }

    protected function getDefaultLabel()
    {
        return $this->t('Delete WmContent');
    }
}
