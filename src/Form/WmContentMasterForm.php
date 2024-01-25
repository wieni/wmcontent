<?php

namespace Drupal\wmcontent\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountProxyInterface;
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
    /** @var AccountProxyInterface */
    protected $currentUser;

    public static function create(ContainerInterface $container)
    {
        $instance = new static();
        $instance->entityTypeManager = $container->get('entity_type.manager');
        $instance->requestStack = $container->get('request_stack');
        $instance->destination = $container->get('redirect.destination');
        $instance->wmContentManager = $container->get('wmcontent.manager');
        $instance->currentUser = $container->get('current_user');

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
        if ($container === null) {
            throw new \InvalidArgumentException('Container is required');
        }
        if ($host === null) {
            throw new \InvalidArgumentException('Host is required');
        }

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
            $bundle = $config['child_bundles_default'];
            $label = $config['child_bundles'][$config['child_bundles_default']];
            $config['child_bundles'] = [$bundle => $label] + $config['child_bundles'];
        }

        foreach ($config['child_bundles'] as $bundle => $label) {
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
                '#title' => $label,
                '#type' => 'link',
                '#attributes' => [
                    'class' => [
                        'button',
                        'button--small',
                    ],
                ],
            ];
        }

        $form['wrapper']['actions'] = [
            '#type' => 'actions',
        ];

        $form['wrapper']['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save the order'),
            '#weight' => 0,
            '#access' => !empty(Element::children($form['wrapper']['rows'])),
        ];

        if (
            $container->hasInlineRenderEnabled()
            && $this->currentUser->hasPermission('view wmcontent inline render')
        ) {
            $form['inline_render_wrapper'] = [
                '#type' => 'container',
                '#weight' => 10,
            ];
            $form['inline_render_wrapper']['inline_render'] = [
                '#type' => 'html_tag',
                '#tag' => 'iframe',
                '#attributes' => [
                    'src' => $this->getHostPreviewUrl($host),
                    'allow' => 'fullscreen',
                    'width' => 360,
                    'height' => 800,
                    'loading' => 'eager',
                    'class' => ['wmcontent__inline-render'],
                ],
            ];

            // add class for special styling in master_form.css
            $form['#attributes']['class'][] = 'wm-content-master-form__with-inline-render';
        }


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
            if (!isset($row['hiddens']['type'], $row['hiddens']['id'])) {
                continue;
            }

            $child = $this->entityTypeManager
                ->getStorage($row['hiddens']['type'])
                ->load($row['hiddens']['id']);

            if (!$child) {
                continue;
            }

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
            // Since Drupal 10.1, all delete operations are handled through ajax
            // Drupal assumes a ConfirmFormBase is used which will be rendered
            // in a modal now.
            // see https://www.drupal.org/project/drupal/issues/2253257
            // Instead, we replace the default delete url with our own where we
            // do an immediate delete and redirect back to the current form.
            // Ideally we _can_ replace this with some kind of ajax operation,
            // it would greatly improve the UX. But how often does an editor
            // make actual use of this delete button? Is it worth the effort?
            //
            // For now, we'll disable the ajax magic by removing the 'use-ajax'
            // class set by Drupal.
            if (isset($operations['delete']['attributes']['class'])) {
                $operations['delete']['attributes']['class'] = array_diff(
                    $operations['delete']['attributes']['class'],
                    ['use-ajax']
                );
            }
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

        if ($language = $request?->query->get('language_content_entity')) {
            $query['language_content_entity'] = $language;
        }

        return $query;
    }

    protected function getHostPreviewUrl(ContentEntityInterface $host): string
    {
        return Url::fromRoute(
            'entity.' . $host->getEntityTypeId() . '.canonical',
            [$host->getEntityTypeId() => $host->id()],
            [
                'absolute' => true,
                'query' => [
                    'wmcontent_inline_render' => 'true',
                ]
            ]
        )->toString();
    }
}
