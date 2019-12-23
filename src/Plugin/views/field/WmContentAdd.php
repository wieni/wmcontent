<?php

namespace Drupal\wmcontent\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityLink;

/**
 * @ViewsField("wmcontent_add_link")
 */
class WmContentAdd extends EntityLink
{
    protected function getEntityLinkTemplate()
    {
        return 'drupal:wmcontent-add';
    }

    protected function getDefaultLabel()
    {
        return $this->t('Add WmContent');
    }
}
