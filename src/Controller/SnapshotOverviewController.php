<?php

namespace Drupal\wmcontent\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\wmcontent\Entity\Snapshot;
use Drupal\wmcontent\Form\Snapshot\SnapshotCreateForm;
use Drupal\wmcontent\Form\Snapshot\SnapshotFormBase;
use Drupal\wmcontent\Form\Snapshot\SnapshotImportForm;
use Drupal\wmcontent\Form\Snapshot\SnapshotRestoreForm;
use Drupal\wmcontent\Service\Snapshot\SnapshotListBuilderInterface;
use Drupal\wmcontent\WmContentContainerInterface;
use Drupal\wmcontent\WmContentManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class SnapshotOverviewController implements ContainerInjectionInterface
{
    use StringTranslationTrait;

    /** @var EntityTypeBundleInfoInterface */
    protected $entityTypeBundleInfo;
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var \Drupal\Core\Form\FormBuilderInterface */
    protected $formBuilder;
    /** @var MessengerInterface */
    protected $messenger;
    /** @var AccountProxyInterface */
    protected $currentUser;
    /** @var WmContentManager */
    protected $wmContentManager;
    /** @var \Drupal\wmcontent\Service\Snapshot\SnapshotService */
    protected $snapshotService;

    public static function create(ContainerInterface $container)
    {
        $instance = new static;
        $instance->entityTypeBundleInfo = $container->get('entity_type.bundle.info');
        $instance->entityTypeManager = $container->get('entity_type.manager');
        $instance->formBuilder = $container->get('form_builder');
        $instance->messenger = $container->get('messenger');
        $instance->currentUser = $container->get('current_user');
        $instance->wmContentManager = $container->get('wmcontent.manager');
        $instance->snapshotService = $container->get('wmcontent.snapshot');

        return $instance;
    }

    public function overview(WmContentContainerInterface $container, ContentEntityInterface $host)
    {
        $listBuilder = $this->entityTypeManager->getListBuilder('wmcontent_snapshot');
        if ($listBuilder instanceof SnapshotListBuilderInterface) {
            $listBuilder->setContainer($container);
            $listBuilder->setHost($host);
        }
        return $listBuilder->render();
    }

    public function overviewTitle(WmContentContainerInterface $container, ContentEntityInterface $host)
    {
        $bundleInfo = $this->entityTypeBundleInfo->getAllBundleInfo();
        $type = $bundleInfo[$host->getEntityTypeId()][$host->bundle()]['label'];

        return $this->t(
            'Snapshots of %type %host',
            [
                '%type' => $type,
                '%host' => (string) $host->label(),
            ]
        );
    }

    public function createSnapshot(Request $request, WmContentContainerInterface $container, ContentEntityInterface $host)
    {
        $form = $this->formBuilder->getForm(
            SnapshotCreateForm::class,
            $container,
            $host
        );
        return $form;
    }

    public function createTitle(WmContentContainerInterface $container, ContentEntityInterface $host)
    {
        $bundleInfo = $this->entityTypeBundleInfo->getAllBundleInfo();
        $type = $bundleInfo[$host->getEntityTypeId()][$host->bundle()]['label'];

        return $this->t(
            'Create new snapshot of %type %host',
            [
                '%type' => $type,
                '%host' => (string) $host->label(),
            ]
        );
    }

    public function import(Request $request, WmContentContainerInterface $container, ContentEntityInterface $host)
    {
        return $this->formBuilder->getForm(
            SnapshotImportForm::class,
            $container,
            $host
        );
    }

    public function importTitle(WmContentContainerInterface $container, ContentEntityInterface $host)
    {
        $bundleInfo = $this->entityTypeBundleInfo->getAllBundleInfo();
        $type = $bundleInfo[$host->getEntityTypeId()][$host->bundle()]['label'];

        return $this->t(
            'Import existing snapshot to %type %host',
            [
                '%type' => $type,
                '%host' => (string) $host->label(),
            ]
        );
    }

    public function restore(Request $request, WmContentContainerInterface $container, ContentEntityInterface $host, Snapshot $snapshot)
    {
        $form = $this->formBuilder->getForm(
            SnapshotRestoreForm::class,
            $container,
            $host,
            $snapshot
        );
        return $form;
    }

    public function export(Request $request, WmContentContainerInterface $container, ContentEntityInterface $host, Snapshot $snapshot)
    {
        $form = [];

        $form['export'] = [
            '#type' => 'container',
            '#attached' => [
                'library' => [
                    'wmcontent/snapshot_copy_to_clipboard',
                ],
            ],
        ];
        $form['export']['blob'] = [
            '#type' => 'textarea',
            '#value' => $this->snapshotService->export(
                $snapshot
            ),
            '#attributes' => [
                'readonly' => 'readonly',
                'class' => [
                    'wmcontent-snapshot-export-to-clipboard--blob',
                ],
            ],
        ];
        $form['export']['copy_msg'] = [
            '#theme' => 'status_messages',
            '#message_list' => [
                'status' => [
                    'Copied to clipboard',
                ],
            ],
            '#status_headings' => [
                'status' => t('Status message'),
                'error' => t('Error message'),
                'warning' => t('Warning message'),
            ],
            '#attributes' => [
                'class' => [
                    'hidden',
                    'wmcontent-snapshot-export-to-clipboard--msg',
                ],
            ],
        ];
        $form['export']['copy'] = [
            '#type' => 'link',
            '#url' => Url::fromUserInput('/'),
            '#title' => 'Copy to clipboard',
            '#attributes' => [
                'class' => [
                    'button',
                    'wmcontent-snapshot-export-to-clipboard--button',
                ],
            ],
        ];

        $form['export']['cancel'] = [
            '#type' => 'link',
            '#url' => Url::fromRoute(
                "entity.{$host->getEntityTypeId()}.wmcontent_snapshot.overview",
                [
                    $host->getEntityTypeId() => $host->id(),
                    'container' => $container->id(),
                ]
            ),
            '#title' => 'Back',
            '#attributes' => [
                'class' => ['use-ajax'],
                'data-dialog-type' => 'modal',
                'data-dialog-options' => json_encode(
                    SnapshotFormBase::MODAL_DIALOG_OPTIONS
                ),
            ],
        ];
        return $form;
    }

    public function exportTitle(WmContentContainerInterface $container, ContentEntityInterface $host, Snapshot $snapshot)
    {
        $bundleInfo = $this->entityTypeBundleInfo->getAllBundleInfo();
        $type = $bundleInfo[$host->getEntityTypeId()][$host->bundle()]['label'];

        return $this->t(
            'Export snapshot code of %snapshot_title (%snapshot_date)',
            [
                '%snapshot_title' => (string) $snapshot->label(),
                '%snapshot_date' => date('d/m/Y H:i', $snapshot->getCreatedTime()),
                '%type' => $type,
                '%host' => (string) $host->label(),
            ]
        );
    }

    public function restoreTitle(WmContentContainerInterface $container, ContentEntityInterface $host, Snapshot $snapshot)
    {
        $bundleInfo = $this->entityTypeBundleInfo->getAllBundleInfo();
        $type = $bundleInfo[$host->getEntityTypeId()][$host->bundle()]['label'];

        return $this->t(
            'Restore snapshot of %snapshot_date to %type %host',
            [
                '%snapshot_title' => (string) $snapshot->label(),
                '%snapshot_date' => date('d/m/Y H:i', $snapshot->getCreatedTime()),
                '%type' => $type,
                '%host' => (string) $host->label(),
            ]
        );
    }

    public function delete(WmContentContainerInterface $container, ContentEntityInterface $child, ContentEntityInterface $host, Snapshot $snapshot)
    {
        $snapshot->delete();

        $this->messenger->addStatus(
            $this->t(
                'Snapshot has been deleted.'
            )
        );

        return new RedirectResponse(
            Url::fromRoute(
                'entity.' . $container->getHostEntityType() . '.wmcontent_snapshot_overview',
                [
                    $container->getHostEntityType() => $host->id(),
                    'container' => $container->id(),
                ]
            )->toString()
        );
    }
}
