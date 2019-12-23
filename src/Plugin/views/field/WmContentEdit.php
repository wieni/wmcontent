<?php

namespace Drupal\wmcontent\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityLink;

/**
 * @ViewsField("wmcontent_edit_link")
 */
class WmContentEdit extends EntityLink
{
    protected function getEntityLinkTemplate()
    {
        return 'drupal:wmcontent-edit';
    }

    protected function getDefaultLabel()
    {
        return $this->t('Edit WmContent');
    }
}
