<?php

namespace Drupal\wmcontent\Form\Snapshot;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * @property \Drupal\wmcontent\Entity\Snapshot $entity
 */
class SnapshotEntityForm extends ContentEntityForm
{
    public function form(array $form, FormStateInterface $form_state)
    {
        $form = parent::form($form, $form_state);

        $denyList = [
            'blob',
            'environment',
            'wmcontent_container',
            'user_id',
            'source_entity_type',
            'source_entity_id',
            'active',
        ];

        foreach ($denyList as $field) {
            if (isset($form[$field])) {
                $form[$field]['#access'] = false;
            }
        }
        return $form;
    }
}
