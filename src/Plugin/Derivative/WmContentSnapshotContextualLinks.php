<?php

namespace Drupal\wmcontent\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\wmcontent\Form\Snapshot\SnapshotFormBase;
use Drupal\wmcontent\WmContentContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WmContentSnapshotContextualLinks extends DeriverBase implements ContainerDeriverInterface
{
    use StringTranslationTrait;

    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager
    ) {
        $this->entityTypeManager = $entityTypeManager;
    }

    public static function create(ContainerInterface $container, $base_plugin_id)
    {
        return new static(
            $container->get('entity_type.manager')
        );
    }

    public function getDerivativeDefinitions($base_plugin_definition)
    {
        $storage = $this->entityTypeManager->getStorage('wmcontent_container');

        /** @var WmContentContainerInterface $container */
        foreach ($storage->loadMultiple() as $container) {
            $config = $container->getConfig();

            $actions = [
                'overview' => [
                    'title' => $this->t('Snapshots'),
                    'route_name' => 'entity.' . $config['host_entity_type'] . '.wmcontent_snapshot.overview',
                    'appears_on' => [
                        'entity.' . $config['host_entity_type'] . '.wmcontent_overview',
                    ],
                ],
                'import' => [
                    'title' => $this->t('Import snapshot'),
                    'route_name' => 'entity.' . $config['host_entity_type'] . '.wmcontent_snapshot.import',
                    'appears_on' => [
                        'entity.' . $config['host_entity_type'] . '.wmcontent_snapshot.overview',
                    ],
                ],
                'create' => [
                    'title' => $this->t('Create snapshot'),
                    'route_name' => 'entity.' . $config['host_entity_type'] . '.wmcontent_snapshot.create',
                    'appears_on' => [
                        'entity.' . $config['host_entity_type'] . '.wmcontent_snapshot.overview',
                    ],
                ],
            ];

            foreach ($actions as $name => $action) {
                $key = $config['host_entity_type'] . $name;

                if (empty($action['disable_ajax'])) {
                    $action['options']['attributes'] = [
                        'class' => ['use-ajax'],
                        'data-dialog-type' => 'modal',
                        'data-dialog-options' => json_encode(
                            SnapshotFormBase::MODAL_DIALOG_OPTIONS,
                            JSON_THROW_ON_ERROR
                        ),
                    ];
                    // Todo: load core/drupal.dialog.ajax when this link is shown
                    // $action['#attached']['library'][] = 'core/drupal.dialog.ajax';
                }

                $this->derivatives[$key] = $action;
            }
        }

        return parent::getDerivativeDefinitions($base_plugin_definition);
    }
}
