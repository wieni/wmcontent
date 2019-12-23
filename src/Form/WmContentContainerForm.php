<?php

namespace Drupal\wmcontent\Form;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\wmcontent\WmContentContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @property WmContentContainerInterface $entity
 */
class WmContentContainerForm extends EntityForm
{
    /** @var MessengerInterface */
    protected $messenger;

    public static function create(ContainerInterface $container)
    {
        $instance = parent::create($container);
        $instance->messenger = $container->get('messenger');

        return $instance;
    }

    public function form(array $form, FormStateInterface $form_state)
    {
        if ($this->operation === 'edit') {
            $form['#title'] = $this->t('Edit container: @name', ['@name' => $this->entity->getLabel()]);
        }

        $entityTypeOptions = $this->getEntityTypeOptions();

        $form['wrapper'] = [
            '#prefix' => '<div id="wholewrapper">',
            '#suffix' => '</div>',
        ];

        $form['wrapper']['label'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Container name'),
            '#default_value' => $this->entity->label(),
            '#size' => 30,
            '#required' => true,
            '#maxlength' => 64,
            '#description' => $this->t('The name for this container.'),
        ];

        $form['wrapper']['id'] = [
            '#type' => 'machine_name',
            '#default_value' => $this->entity->id(),
            '#required' => true,
            '#disabled' => !$this->entity->isNew(),
            '#size' => 30,
            '#maxlength' => 64,
            '#machine_name' => [
                'exists' => '\Drupal\wmcontent\Entity\WmContentContainer::load',
            ],
        ];

        $form['wrapper']['host_entity_type'] = [
            '#type' => 'select',
            '#title' => $this->t('Host entity type'),
            '#default_value' => $this->entity->getHostEntityType(),
            '#empty_option' => $this->t('Choose an entity type'),
            '#options' => $entityTypeOptions,
            '#validated' => true,
            '#required' => true,
            '#description' => $this->t('The host entity type to which attach content to.'),
            '#ajax' => [
                'callback' => '::updateForm',
                'wrapper' => 'wholewrapper',
                'progress' => [
                    'type' => 'throbber',
                    'message' => 'searching',
                ],
            ],
        ];

        $form['wrapper']['host_bundles_fieldset'] = [
            '#title' => t('Host Bundles'),
            '#prefix' => '<div id="host-checkboxes-div">',
            '#suffix' => '</div>',
            '#type' => 'fieldset',
            '#description' => t('Allowed bundles in this type.'),
        ];

        $form['wrapper']['host_bundles_fieldset']['host_bundles'] = [
            '#type' => 'checkboxes',
            '#options' => $this->entity->getHostBundlesAll(),
            '#default_value' => $this->entity->getHostBundles(),
        ];

        $form['wrapper']['child_entity_type'] = [
            '#type' => 'select',
            '#title' => $this->t('Child entity type'),
            '#default_value' => $this->entity->getChildEntityType(),
            '#empty_option' => $this->t('Choose an entity type'),
            '#options' => $entityTypeOptions,
            '#validated' => true,
            '#required' => true,
            '#description' => $this->t('The child entity type to which attach content to.'),
            '#ajax' => [
                'callback' => '::updateForm',
                'wrapper' => 'wholewrapper',
                'progress' => [
                    'type' => 'throbber',
                    'message' => 'searching',
                ],
            ],
        ];

        $form['wrapper']['child_bundles_fieldset'] = [
            '#title' => t('Child Bundles'),
            '#prefix' => '<div id="child-checkboxes-div">',
            '#suffix' => '</div>',
            '#type' => 'fieldset',
            '#description' => t('Allowed bundles in this type.'),
        ];

        $form['wrapper']['child_bundles_fieldset']['child_bundles'] = [
            '#type' => 'checkboxes',
            '#options' => $this->entity->getChildBundlesAll(),
            '#default_value' => $this->entity->getChildBundles(),
        ];

        $form['wrapper']['child_bundles_default'] = [
            '#title' => t('Default'),
            '#type' => 'select',
            '#options' => $this->entity->getChildBundlesAll(),
            '#default_value' => $this->entity->getChildBundlesDefault(),
        ];

        $form['hide_single_option_sizes'] = [
            '#type' => 'checkbox',
            '#default_value' => $this->entity->getHideSingleOptionSizes(),
            '#title' => $this->t('Hide single option sizes'),
        ];

        $form['hide_single_option_alignments'] = [
            '#type' => 'checkbox',
            '#default_value' => $this->entity->getHideSingleOptionAlignments(),
            '#title' => $this->t('Hide single option alignments'),
        ];

        $form['show_size_column'] = [
            '#type' => 'checkbox',
            '#default_value' => $this->entity->getShowSizeColumn(),
            '#title' => $this->t('Show the size column'),
        ];

        $form['show_alignment_column'] = [
            '#type' => 'checkbox',
            '#default_value' => $this->entity->getShowAlignmentColumn(),
            '#title' => $this->t('Show the alignment column'),
        ];

        return parent::form($form, $form_state);
    }

    public function save(array $form, FormStateInterface $form_state)
    {
        // Prevent leading and trailing spaces.
        $this->entity->set('label', trim($this->entity->label()));

        $host_bundles = $this->entity->get('host_bundles');
        $host_bundles = array_filter($host_bundles);
        $this->entity->set('host_bundles', $host_bundles);

        $child_bundles = $this->entity->get('child_bundles');
        $child_bundles = array_filter($child_bundles);
        $this->entity->set('child_bundles', $child_bundles);

        $status = $this->entity->save();

        $action = $status === SAVED_UPDATED ? 'updated' : 'added';

        $this->messenger->addStatus($this->t(
            'Container %label has been %action.',
            [
                '%label' => $this->entity->label(),
                '%action' => $action,
            ]
        ));
        $this->logger('wmcontent')->notice(
            'Container %label has been %action.',
            [
                '%label' => $this->entity->label(),
                '%action' => $action,
            ]
        );

        $form_state->setRedirect('wmcontent.collection');
    }

    public function updateForm($form, FormStateInterface $form_state)
    {
        $form_state->setRebuild(true);

        return $form['wrapper'];
    }

    protected function actions(array $form, FormStateInterface $form_state)
    {
        $actions = parent::actions($form, $form_state);
        $actions['submit']['#value'] = $this->t('Update container');

        if ($this->entity->isNew()) {
            $actions['submit']['#value'] = $this->t('Add container');
        }

        return $actions;
    }

    protected function getEntityTypeOptions(): array
    {
        $entityTypes = array_filter(
            $this->entityTypeManager->getDefinitions(),
            static function (EntityTypeInterface $entityType) {
                return $entityType instanceof ContentEntityTypeInterface
                    && $entityType->hasLinkTemplate('canonical');
            }
        );

        $options = [];
        foreach ($entityTypes as $id => $entityType) {
            $options[$id] = $entityType->getLabel();
        }

        ksort($options);

        return $options;
    }
}
