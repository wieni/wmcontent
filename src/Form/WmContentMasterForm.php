<?php

namespace Drupal\wmcontent\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\wmcontent\WmContentManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal;

/**
 * Class WmContentMasterForm.
 *
 * @package Drupal\wmcontent\Form
 */
class WmContentMasterForm extends FormBase
{

    /**
     * Drupal\wmcontent\WmContentManager definition.
     *
     * @var Drupal\wmcontent\WmContentManager
     */
    protected $wmContentManager;

    /**
     * The main entity that we are adding and managing paragraphs for.
     */
    protected $host;
    
    /**
     * @var Drupal\wmcontent\Entity\WmContentContainer $container
     */
    protected $container;

    public function __construct(
        WmContentManager $wmcontent_manager,
        EntityInterface $host,
        $container
    ) {
        $this->wmContentManager = $wmcontent_manager;
        $this->host = $host;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'wm_content_master_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->container->getConfig();
    
        // Get the children.
        $list = $this->wmContentManager->getContent(
            $this->host,
            $this->container->getId()
        );
        
        // The query (including the destination) Will be the same for all actions.
        // We must add our own language param, however, since adding it in via
        // link parameters is a no go.
        $query = [
            'destination' => Url::fromRoute(
                "entity." . $this->container->getHostEntityType() . ".wmcontent_overview",
                [
                    $this->container->getHostEntityType() => $this->host->id(),
                    'container' => $this->container->getId(),
                ]
            )->toString(),
        ];
        if (!empty($_GET['language_content_entity'])) {
            $query['language_content_entity'] = $_GET['language_content_entity'];
        }
        
        // Some internal values.
        $form['container'] = [
            '#type' => 'value',
            '#value' => $this->container,
        ];

        // Some internal values.
        $form['host'] = [
            '#type' => 'value',
            '#value' => $this->host,
        ];

        $header = [
            '',
            t('Type'),
            t('Content'),
            t('Size'),
            t('Alignment'),
            t('Weight'),
            t('Operations'),
        ];

        // Put it in a Table.
        $form['rows'] = [
            '#tree' => true,
            '#type' => 'table',
            '#header' => $header,
            '#tabledrag' => [
                [
                    'action' => 'order',
                    'relationship' => 'sibling',
                    'group' => 'wmcontent_weight',
                ],
            ],
        ];
        
        /** @var Drupal\eck\Entity\EckEntity $child */
        foreach ($list as $child) {
            // Edit and delete operations.
            $operations = [
                'data' => [
                  '#type' => 'operations',
                  '#links' => [
                    'edit' => [
                        'url' => Url::fromRoute(
                            "entity." . $this->container->getHostEntityType() . ".wmcontent_edit",
                            [
                                'container' => $this->container->getId(),
                                'type' => $child->getEntityTypeId(),
                                'child_id' => $child->id(),
                                $this->container->getHostEntityType() => $this->host->id(),
                            ],
                            [
                                'query' => $query,
                            ]
                        ),
                        'title' => $this->t('Edit'),
                    ],
                    'delete' => [
                        'url' => Url::fromRoute(
                            "entity." . $this->container->getHostEntityType() . ".wmcontent_delete",
                            [
                              'container' => $this->container->getId(),
                              'type' => $child->getEntityTypeId(),
                              'child_id' => $child->id(),
                              $this->container->getHostEntityType() => $this->host->id(),
                            ],
                            [
                              'query' => $query,
                            ]
                        ),
                        'title' => $this->t('Delete'),
                    ],
                  ],
                ],
            ];

            // Start the row.
            $row = [
                '#attributes' => array(
                    'class' => array('draggable'),
                ),
            ];

            // Put type and id in a hidden.
            $row['hiddens'] = [];
            $row['hiddens']['id'] =[
                '#type' => 'hidden',
                '#value' => $child->id(),
            ];

            $row['hiddens']['type'] =[
                '#type' => 'hidden',
                '#value' => $child->getEntityTypeId(),
            ];
    
            $row['hiddens']['bundle'] =[
                '#type' => 'hidden',
                '#value' => $child->get('type')->entity->id(),
            ];

            // Bundle label.
            $row['bundle'] = [
                '#type' => 'container',
                '#markup' => $child->get('type')->entity->label(),
            ];

            // Teaser.
            $row['content'] = [
                '#type' => 'container',
                '#markup' => $this->wmContentManager->getEntityTeaser($child),
            ];

            // Size?
            $row['size'] = [
                '#type' => 'container',
                '#markup' => $child->get('wmcontent_size')->getString(),
            ];

            // Size?
            $row['alignment'] = [
                '#type' => 'container',
                '#markup' => $child->get('wmcontent_alignment')->getString(),
            ];

            // Weight.
            $row['wmcontent_weight'] = [
                '#type' => 'weight',
                '#default_value' => $child->get('wmcontent_weight')->getString(),
                '#attributes' => array(
                    'class' => array('wmcontent_weight', 'wmcontent_weight-' . $child->id()),
                ),
                '#delta' => 100,
            ];

            // Add the operations.
            $row['operations'] = $operations;

            // Add the row to the rows.
            $form['rows'][] = $row;
        }

        // Make some add links.
        $links = [];
        
        foreach ($config['child_bundles'] as $bundle) {
            $links[$bundle] = array(
                'title' => $this->t(
                    'Add %label',
                    [
                        '%label' => $this->wmContentManager->getLabel($config['child_entity_type'], $bundle)
                    ]
                ),
                'url' => Url::fromRoute(
                    "entity." . $this->container->getHostEntityType() . ".wmcontent_add",
                    [
                        'bundle' => $bundle,
                        $this->container->getHostEntityType() => $this->host->id(),
                        'container' => $this->container->getId(),
                    ],
                    [
                        'query' => $query,
                    ]
                ),
            );
        }

        // Submit or add.
        $form['actions'] = [
            '#type' => 'actions',
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => t('Save the order'),
            '#weight' => 0,
        ];

        $form['actions']['add_new'] = [
            '#type' => 'dropbutton',
            '#links' => $links,
            '#weight' => 1,
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {

        // Get the values from the form.
        $values = $form_state->getValues();

        // Go through each row and update the weight.
        $p = false;
        foreach ($values['rows'] as $row) {
            /** @var Drupal\eck\Entity\EckEntity $p */
            $p = Drupal::entityTypeManager()->getStorage($row['hiddens']['type'])->load($row['hiddens']['id']);
            $p->set('wmcontent_weight', $row['wmcontent_weight']);
            $p->save();
        }
        
        if ($p) {
            // Clear Drupal cache for the parent entity.
            $this->wmContentManager->hostClearCache($p);
        }
    }
}
