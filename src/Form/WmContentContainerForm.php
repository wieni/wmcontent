<?php

/**
 * @file
 * Contains \Drupal\wmcontent\Form\WmContentContainerForm.
 */

namespace Drupal\wmcontent\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Component\Utility\Xss;
use Drupal\eck\Entity\EckEntityType;
use Drupal\wmcontent\Entity\WmContentContainer;

/**
 * Form controller for the container entity type edit form.
 */
class WmContentContainerForm extends EntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state)
    {
        // Get the values.
        $values = $form_state->getValues();
        $types = $this->getContentEntityTypes();
        $firsttype = array_keys($types)[0];

        /** @var WmContentContainer $entity */
        $entity = $this->entity;

        // Change page title for the edit operation.
        if ($this->operation == 'edit') {
            $form['#title'] = $this->t('Edit container: @name', array('@name' => $entity->getLabel()));
        }

        $form['wrapper'] = [
            '#prefix' => '<div id="wholewrapper">',
            '#suffix' => '</div>',
        ];

        $form['wrapper']['label'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Container name'),
            '#default_value' => $entity->label(),
            '#size' => 30,
            '#required' => true,
            '#maxlength' => 64,
            '#description' => $this->t('The name for this container.'),
        );

        $form['wrapper']['id'] = array(
            '#type' => 'machine_name',
            '#default_value' => $entity->id(),
            '#required' => true,
            '#disabled' => !$entity->isNew(),
            '#size' => 30,
            '#maxlength' => 64,
            '#machine_name' => [
                'exists' => '\Drupal\wmcontent\Entity\WmContentContainer::load',
            ],
        );

        $form['wrapper']['host_entity_type'] = array(
            '#type' => 'select',
            '#title' => $this->t('Host entity type'),
            '#default_value' => $entity->getHostEntityType(),
            '#options' => $this->getContentEntityTypes(),
            '#validated' => true,
            '#required' => true,
            '#description' => $this->t('The host entity type to which attach content to.'),
            '#ajax' => [
                'callback' => '::updateForm',
                'wrapper' => 'wholewrapper',
                'progress' => [
                    'type' => 'throbber',
                    'message' => "searching",
                ],
            ],
        );

        $form['wrapper']['host_bundles_fieldset'] = [
            '#title' => t('Host Bundles'),
            '#prefix' => '<div id="host-checkboxes-div">',
            '#suffix' => '</div>',
            '#type' => 'fieldset',
            '#description' => t('Allowed bundles in this type.'),
        ];

        $host_bundles = [];
        if ($entity->getHostEntityType()) {
            $host_bundles = $this->getAllBundles($entity->getHostEntityType());
        } elseif (isset($values['host_entity_type'])) {
            $host_bundles = $this->getAllBundles($values['host_entity_type']);
        } else {
            $host_bundles = $this->getAllBundles($firsttype);
        }

        $form['wrapper']['host_bundles_fieldset']['host_bundles'] = [
            '#type' => 'checkboxes',
            '#options' => $host_bundles,
            '#default_value' => $entity->getHostBundles(),
        ];


        $form['wrapper']['child_entity_type'] = array(
            '#type' => 'select',
            '#title' => $this->t('Child entity type'),
            '#default_value' => $entity->getChildEntityType(),
            '#options' => $this->getContentEntityTypes(),
            '#validated' => true,
            '#required' => true,
            '#description' => $this->t('The child entity type to which attach content to.'),
            '#ajax' => [
                'callback' => '::updateForm',
                'wrapper' => 'wholewrapper',
                'progress' => [
                    'type' => 'throbber',
                    'message' => "searching",
                ],
            ],
        );

        $form['wrapper']['child_bundles_fieldset'] = [
            '#title' => t('Child Bundles'),
            '#prefix' => '<div id="child-checkboxes-div">',
            '#suffix' => '</div>',
            '#type' => 'fieldset',
            '#description' => t('Allowed bundles in this type.'),
        ];

        $child_bundles = [];
        if ($entity->getHostEntityType()) {
            $child_bundles = $this->getAllBundles($entity->getChildEntityType());
        } elseif (isset($values['child_entity_type'])) {
            $child_bundles = $this->getAllBundles($values['child_entity_type']);
        } else {
            $child_bundles = $this->getAllBundles($firsttype);
        }

        $form['wrapper']['child_bundles_fieldset']['child_bundles'] = [
            '#type' => 'checkboxes',
            '#options' => $child_bundles,
            '#default_value' => $entity->getChildBundles(),
        ];

        $form['hide_single_option_sizes'] = [
            '#type' => 'checkbox',
            '#default_value' => $entity->getHideSingleOptionSizes(),
            '#title' => $this->t('Hide single option sizes'),
        ];

        $form['hide_single_option_alignments'] = [
            '#type' => 'checkbox',
            '#default_value' => $entity->getHideSingleOptionAlignments(),
            '#title' => $this->t('Hide single option alignments'),
        ];

        return parent::form($form, $form_state, $entity);
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state)
    {
        /** @var WmContentContainer $entity */
        $entity = $this->entity;

        // Prevent leading and trailing spaces.
        $entity->set('label', trim($entity->label()));

        $host_bundles = $entity->get('host_bundles');
        $host_bundles = array_filter($host_bundles);
        $entity->set('host_bundles', $host_bundles);

        $child_bundles = $entity->get('child_bundles');
        $child_bundles = array_filter($child_bundles);
        $entity->set('child_bundles', $child_bundles);

        $status = $entity->save();

        $edit_link = $this->entity->link($this->t('Edit'));
        $action = $status == SAVED_UPDATED ? 'updated' : 'added';

        // Tell the user we've updated their container.
        drupal_set_message($this->t(
            'Container %label has been %action.',
            [
                '%label' => $entity->label(),
                '%action' => $action
            ]
        ));
        $this->logger('wmcontent')->notice(
            'Container %label has been %action.',
            array('%label' => $entity->label(), 'link' => $edit_link)
        );

        // Redirect back to the list view.
        $form_state->setRedirect('wmcontent.collection');
    }

    /**
     * {@inheritdoc}
     */
    protected function actions(array $form, FormStateInterface $form_state)
    {
        $actions = parent::actions($form, $form_state);
        $actions['submit']['#value'] = $this->t('Update container');
        if ($this->entity->isNew()) {
            $actions['submit']['#value'] = $this->t('Add container');
        }
        return $actions;
    }

    /**
     * Ideally filter out only content entity types here. ECK seems to be
     * a config type so however, so bollocks.
     */
    private function getContentEntityTypes()
    {
        $types = [];

        $types['node'] = 'Node';
        $types['taxonomy_term'] = 'Taxonomy Term';

        $eck_types = EckEntityType::loadMultiple();
        foreach ($eck_types as $machine => $type) {
            $types[$machine] = $type->label();
        }

        ksort($types);

        return $types;
    }

    /**
     * Ideally filter this out. For now show all.
     */
    private function getAllBundles($type)
    {
        if (!$type) {
            return [];
        }
        // Build master bundles list.
        $bundlesraw = $this->entityManager->getAllBundleInfo();
        $bundles = [];
        foreach ($bundlesraw as $k => $v) {
            if ($k == $type) {
                foreach ($v as $p => $q) {
                    $bundles[$p] = $q['label'];
                }
            }
        }
        ksort($bundles);
        return $bundles;
    }

    public function updateForm($form, FormStateInterface $form_state)
    {
        $form_state->setRebuild(true);
        return $form['wrapper'];
    }
}
