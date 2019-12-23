<?php

namespace Drupal\wmcontent\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityLink;

/**
 * @ViewsField("wmcontent_overview_link")
 */
class WmContentOverview extends EntityLink
{
    protected function getEntityLinkTemplate()
    {
        return 'drupal:wmcontent-overview';
    }

    protected function getDefaultLabel()
    {
        return $this->t('WmContent');
    }
}
