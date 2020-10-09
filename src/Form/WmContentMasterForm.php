<?php

namespace Drupal\wmcontent\Form;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\wmcontent\WmContentContainerInterface;
use Drupal\wmcontent\WmContentManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class WmContentMasterForm implements FormInterface, ContainerInjectionInterface
{
    use DependencySerializationTrait;
    use StringTranslationTrait;

    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var RequestStack */
    protected $requestStack;
    /** @var RedirectDestinationInterface */
    protected $destination;
    /** @var WmContentManager */
    protected $wmContentManager;

    public static function create(ContainerInterface $container)
    {
        $instance = new static();
        $instance->entityTypeManager = $container->get('entity_type.manager');
        $instance->requestStack = $container->get('request_stack');
        $instance->destination = $container->get('redirect.destination');
        $instance->wmContentManager = $container->get('wmcontent.manager');

        return $instance;
    }

    public function getFormId()
    {
        return 'wm_content_master_form';
    }

    public function title(WmContentContainerInterface $container, ContentEntityInterface $host)
    {
        return $this->t(
            '%slug for %label',
            [
                '%slug' => $container->getLabel(),
                '%label' => $host->label(),
            ]
        );
    }

    public function buildForm(array $form, FormStateInterface $form_state, ?ContentEntityInterface $host = null, ?WmContentContainerInterface $container = null)
    {
        $config = $container->getConfig();
        $children = $this->wmContentManager->getContent($host, $container->getId());

        $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
        $form['#attached']['library'][] = 'wmcontent/master_form';

        $form['container'] = [
            '#type' => 'value',
            '#value' => $container,
        ];

        $form['host'] = [
            '#type' => 'value',
            '#value' => $host,
        ];

        $form['wrapper'] = [
            '#type' => 'container',
            '#weight' => 5,
        ];

        $form['wrapper']['rows'] = [
            '#tree' => true,
            '#type' => 'table',
            '#header' => $this->getTableHeader($container),
            '#tabledrag' => [
                [
                    'action' => 'order',
                    'relationship' => 'sibling',
                    'group' => 'wmcontent_weight',
                ],
            ],
        ];

        foreach ($children as $child) {
            if (!$child->access('view')) {
                continue;
            }

            $form['wrapper']['rows'][] = $this->getTableRow($container, $host, $child);
        }

        $form['add_new'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Add new'),
            '#weight' => 0,
        ];

        // Put the default one on first if it is set/existing.
        if (isset($config['child_bundles'][$config['child_bundles_default']])) {
            $key = $config['child_bundles_default'];
            $element = $config['child_bundles'][$config['child_bundles_default']];
            $config['child_bundles'] = [$key => $element] + $config['child_bundles'];
        }

        foreach ($config['child_bundles'] as $bundle) {
            $access = $this->entityTypeManager
                ->getAccessControlHandler($config['child_entity_type'])
                ->createAccess($bundle);

            if (!$access) {
                continue;
            }

            $form['add_new'][$bundle] = [
                '#url' => Url::fromRoute(
                    "entity.{$container->getHostEntityType()}.wmcontent_add",
                    [
                        'bundle' => $bundle,
                        $container->getHostEntityType() => $host->id(),
                        'container' => $container->getId(),
                    ],
                    [
                        'query' => $this->getQueryParams($container, $host),
                    ]
                ),
                '#title' => $this->getLabel($config['child_entity_type'], $bundle),
                '#type' => 'link',
                '#attributes' => [
                    'class' => [
                        'button',
                        'button--small',
                    ],
                ],
            ];
        }

        uasort($form['add_new'], [SortArray::class, 'sortByTitleElement']);

        $form['wrapper']['actions'] = [
            '#type' => 'actions',
        ];

        $form['wrapper']['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save the order'),
            '#weight' => 0,
            '#access' => !empty(Element::children($form['wrapper']['rows'])),
        ];

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state)
    {
    }

    public function submitForm(array &$form, FormStateInterface $formState)
    {
        $request = $this->requestStack->getCurrentRequest();
        $values = $formState->getValues();
        $rows = $values['rows'] ?: [];

        foreach ($rows as $row) {
            $child = $this->entityTypeManager
                ->getStorage($row['hiddens']['type'])
                ->load($row['hiddens']['id']);

            $child->set('wmcontent_weight', $row['wmcontent_weight']);
            $child->save();
        }

        if ($request && $request->isXmlHttpRequest()) {
            $formState->setResponse(new JsonResponse('ok'));
        }
    }

    protected function getTableHeader(WmContentContainerInterface $container): array
    {
        $header = [];
        $header[] = '';
        $header[] = $this->t('Type');
        $header[] = $this->t('Content');

        if ($container->getShowSizeColumn()) {
            $header[] = $this->t('Size');
        }

        if ($container->getShowAlignmentColumn()) {
            $header[] = $this->t('Alignment');
        }

        $header[] = $this->t('Weight');
        $header[] = $this->t('Operations');

        return $header;
    }

    protected function getTableRow(WmContentContainerInterface $container, ContentEntityInterface $host, EntityInterface $child): array
    {
        $row = [
            '#attributes' => [
                'class' => ['draggable'],
            ],
        ];

        $row['hiddens']['id'] = [
            '#type' => 'hidden',
            '#value' => $child->id(),
        ];

        $row['hiddens']['type'] = [
            '#type' => 'hidden',
            '#value' => $child->getEntityTypeId(),
        ];

        $row['hiddens']['bundle'] = [
            '#type' => 'hidden',
            '#value' => $child->get('type')->entity->id(),
        ];

        $row['bundle'] = [
            '#type' => 'container',
            '#markup' => $child->get('type')->entity->label(),
        ];

        $row['content'] = [
            '#type' => 'container',
            '#markup' => $child->label(),
        ];

        if ($container->getShowSizeColumn()) {
            $row['size'] = [
                '#type' => 'container',
                '#markup' => $child->get('wmcontent_size')->getString(),
            ];
        }

        if ($container->getShowAlignmentColumn()) {
            $row['alignment'] = [
                '#type' => 'container',
                '#markup' => $child->get('wmcontent_alignment')->getString(),
            ];
        }

        $row['wmcontent_weight'] = [
            '#type' => 'weight',
            '#default_value' => $child->get('wmcontent_weight')->getString(),
            '#attributes' => [
                'class' => ['wmcontent_weight', "wmcontent_weight-{$child->id()}"],
            ],
            '#delta' => 100,
        ];

        $row['operations'] = [
            'data' => [
                '#type' => 'operations',
                '#links' => $this->getOperations($container, $host, $child),
            ],
        ];

        return $row;
    }

    protected function getOperations(WmContentContainerInterface $container, ContentEntityInterface $host, EntityInterface $child): array
    {
        $query = $this->getQueryParams($container, $host);
        $operations = $this->entityTypeManager
            ->getListBuilder($child->getEntityTypeId())
            ->getOperations($child);

        if ($child->access('update')) {
            $operations['edit']['url'] = Url::fromRoute(
                "entity.{$container->getHostEntityType()}.wmcontent_edit",
                [
                    'container' => $container->getId(),
                    'child' => $child->id(),
                    $container->getHostEntityType() => $host->id(),
                ],
                [
                    'query' => $this->getQueryParams($container, $host),
                ]
            );
        }

        if ($child->access('delete')) {
            $operations['delete']['url'] = Url::fromRoute(
                "entity.{$container->getHostEntityType()}.wmcontent_delete",
                [
                    'container' => $container->getId(),
                    'child' => $child->id(),
                    $container->getHostEntityType() => $host->id(),
                ],
                [
                    'query' => $query,
                ]
            );
        }

        foreach ($operations as $operation) {
            $query = $operation['url']->getOption('query') ?? [];
            $query['destination'] = $this->destination->get();
            $operation['url']->setOption('query', $query);
        }

        return $operations;
    }


    /**
     * The query (including the destination) Will be the same for all actions.
     * We must add our own language param, however, since adding it in via
     * link parameters is a no go.
     */
    protected function getQueryParams(WmContentContainerInterface $container, ContentEntityInterface $host): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $query = [
            'destination' => Url::fromRoute(
                "entity.{$container->getHostEntityType()}.wmcontent_overview",
                [
                    $container->getHostEntityType() => $host->id(),
                    'container' => $container->getId(),
                ]
            )->toString(),
        ];

        if ($language = $request->query->get('language_content_entity')) {
            $query['language_content_entity'] = $language;
        }

        return $query;
    }

    protected function getLabel(string $entityTypeId, string $bundle): string
    {
        $entityType = $this->entityTypeManager
            ->getDefinition($entityTypeId);
        $bundleDefinition = $this->entityTypeManager
            ->getStorage($entityType->getBundleEntityType())
            ->load($bundle);

        return $bundleDefinition->label();
    }
}
