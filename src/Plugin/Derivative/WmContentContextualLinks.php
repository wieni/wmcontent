<?php

namespace Drupal\wmcontent\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\wmcontent\WmContentContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WmContentContextualLinks extends DeriverBase implements ContainerDeriverInterface
{
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
        $storage = $this->entityTypeManager
            ->getStorage('wmcontent_container');

        /** @var WmContentContainerInterface $container */
        foreach ($storage->loadMultiple() as $container) {
            $config = $container->getConfig();

            $key = $config['host_entity_type'] . '.' . $config['id'];

            $this->derivatives[$key]['title'] = $config['label'];
            $this->derivatives[$key]['title']['route_name'] = 'entity.' . $config['host_entity_type'] . '.wmcontent_overview';
            $this->derivatives[$key]['title']['group'] = ['host_entity_type'];
        }

        return parent::getDerivativeDefinitions($base_plugin_definition);
    }
}
