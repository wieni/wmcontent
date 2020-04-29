<?php

namespace Drupal\wmcontent\Form;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
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
    use StringTranslationTrait;

    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var EntityTypeBundleInfoInterface */
    protected $entityTypeBundleInfo;
    /** @var RequestStack */
    protected $requestStack;
    /** @var RedirectDestinationInterface */
    protected $destination;
    /** @var WmContentManager */
    protected $wmContentManager;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        EntityTypeBundleInfoInterface $entityTypeBundleInfo,
        RequestStack $requestStack,
        RedirectDestinationInterface $destination,
        WmContentManager $wmContentManager
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->entityTypeBundleInfo = $entityTypeBundleInfo;
        $this->requestStack = $requestStack;
        $this->destination = $destination;
        $this->wmContentManager = $wmContentManager;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('entity_type.manager'),
            $container->get('entity_type.bundle.info'),
            $container->get('request_stack'),
            $container->get('redirect.destination'),
            $container->get('wmcontent.manager')
        );
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

        // The query (including the destination) Will be the same for all actions.
        // We must add our own language param, however, since adding it in via
        // link parameters is a no go.
        $query = [
            'destination' => Url::fromRoute(
                'entity.' . $container->getHostEntityType() . '.wmcontent_overview',
                [
                    $container->getHostEntityType() => $host->id(),
                    'container' => $container->getId(),
                ]
            )->toString(),
        ];

        if (!empty($_GET['language_content_entity'])) {
            $query['language_content_entity'] = $_GET['language_content_entity'];
        }

        $form['container'] = [
            '#type' => 'value',
            '#value' => $container,
        ];

        $form['host'] = [
            '#type' => 'value',
            '#value' => $host,
        ];

        $header = [];
        $header[] = '';
        $header[] = t('Type');
        $header[] = t('Content');
        if ($container->getShowSizeColumn()) {
            $header[] = t('Size');
        }
        if ($container->getShowAlignmentColumn()) {
            $header[] = t('Alignment');
        }
        $header[] = t('Weight');
        $header[] = t('Operations');

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

        foreach ($children as $child) {
            if (!$child->access('view')) {
                continue;
            }

            $operations = $this->entityTypeManager
                ->getListBuilder($child->getEntityTypeId())
                ->getOperations($child);

            if ($child->access('update')) {
                $operations['edit']['url'] = Url::fromRoute(
                    'entity.' . $container->getHostEntityType() . '.wmcontent_edit',
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

            if ($child->access('delete')) {
                $operations['delete']['url'] = Url::fromRoute(
                    'entity.' . $container->getHostEntityType() . '.wmcontent_delete',
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
                    'class' => ['wmcontent_weight', 'wmcontent_weight-' . $child->id()],
                ],
                '#delta' => 100,
            ];

            $row['operations'] = [
                'data' => [
                    '#type' => 'operations',
                    '#links' => $operations,
                ],
            ];

            $form['rows'][] = $row;
        }

        $links = [];

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

            $links[$bundle] = [
                'title' => $this->t(
                    'Add %label',
                    [
                        '%label' => $this->getLabel($config['child_entity_type'], $bundle),
                    ]
                ),
                'url' => Url::fromRoute(
                    'entity.' . $container->getHostEntityType() . '.wmcontent_add',
                    [
                        'bundle' => $bundle,
                        $container->getHostEntityType() => $host->id(),
                        'container' => $container->getId(),
                    ],
                    [
                        'query' => $query,
                    ]
                ),
            ];
        }

        uasort($links, [SortArray::class, 'sortByTitleElement']);

        $form['actions'] = [
            '#type' => 'actions',
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => t('Save the order'),
            '#weight' => 0,
            '#access' => !empty(Element::children($form['rows'])),
        ];

        $form['actions']['add_new'] = [
            '#type' => 'dropbutton',
            '#links' => $links,
            '#weight' => 1,
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

    private function getLabel(string $entityType, string $bundle): string
    {
        $labels = &drupal_static(__FUNCTION__);
        if (!isset($labels[$entityType])) {
            $labels[$entityType] = $this->entityTypeBundleInfo->getBundleInfo($entityType);
        }

        if (!empty($labels[$entityType][$bundle]['label'])) {
            return $labels[$entityType][$bundle]['label'];
        }

        return ucwords(str_replace('_', ' ', $bundle));
    }
}
