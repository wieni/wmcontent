<?php

namespace Drupal\wmcontent\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
     * The container we are working with.
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

        // Get container config.
        $config = $this->container->getConfig();

        $rows = [];

        // Get the childs.
        $list = $this->wmContentManager->getContent(
            $this->host,
            $this->container->getid()
        );

        // Make a row out of each child.
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
                                'query' => [
                                    'destination' => Url::fromRoute(
                                        "entity." . $this->container->getHostEntityType() . ".wmcontent_overview",
                                        [
                                            $this->container->getHostEntityType() => $this->host->id(),
                                            'container' => $this->container->getId(),
                                        ]
                                    )->toString(),
                                ]
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
                              'query' => [
                                'destination' => Url::fromRoute(
                                    "entity." . $this->container->getHostEntityType() . ".wmcontent_overview",
                                    [
                                     $this->container->getHostEntityType() => $this->host->id(),
                                        'container' => $this->container->getId(),
                                    ]
                                )->toString(),
                              ]
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

            // Bundle label.
            $row['bundle'] = [
                '#type' => 'container',
                '#markup' => $child->type->entity->label(),
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

            // Weight.
            $row['wmcontent_weight'] = [
                '#type' => 'weight',
                '#default_value' => $child->get('wmcontent_weight')->getString(),
                '#attributes' => array(
                    'class' => array('wmcontent_weight', 'wmcontent_weight-' . $child->id()),
                ),
                '#delta' => 100,
            ];

            // Add the oprations.
            $row['operations'] = $operations;

            // Add the row to the rows.
            $form['rows'][] = $row;
        }

        // Make some add links.
        $links = [];

        // Come back to here when you are done.
        $destination = Url::fromRoute(
            "entity." . $this->container->getHostEntityType() . ".wmcontent_overview",
            [
                $this->container->getHostEntityType() => $this->host->id(),
                'container' => $this->container->getId(),
            ]
        )->toString();

        foreach ($config['child_bundles'] as $bundle) {
            $links[$bundle] = array(
                'title' => $this->t('Add @label', array('@label' => $bundle)),
                'url' => Url::fromRoute(
                    "entity." . $this->container->getHostEntityType() . ".wmcontent_add",
                    [
                        'bundle' => $bundle,
                        $this->container->getHostEntityType() => $this->host->id(),
                        'container' => $this->container->getId(),
                        'destination' => $destination,
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

        // Go through each row and resave the weight.
        foreach ($values['rows'] as $row) {
            $p = Drupal::entityTypeManager()->getStorage($row['hiddens']['type'])->load($row['hiddens']['id']);
            $p->set('wmcontent_weight', $row['wmcontent_weight']);
            $p->save();
        }

        // Clear Drupals cache for the parent entity.
        $this->wmContentManager->hostClearCache($p);
    }
}
