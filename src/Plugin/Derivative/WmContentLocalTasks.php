<?php

namespace Drupal\wmcontent\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\wmcontent\WmContentContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WmContentLocalTasks extends DeriverBase implements ContainerDeriverInterface
{
    use StringTranslationTrait;

    /** @var string */
    protected $basePluginId;
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;

    public function __construct(
        string $basePluginId,
        EntityTypeManagerInterface $entityTypeManager,
        TranslationInterface $stringTranslation
    ) {
        $this->basePluginId = $basePluginId;
        $this->entityTypeManager = $entityTypeManager;
        $this->stringTranslation = $stringTranslation;
    }

    public static function create(ContainerInterface $container, $basePluginId)
    {
        return new static(
            $basePluginId,
            $container->get('entity_type.manager'),
            $container->get('string_translation')
        );
    }

    public function getDerivativeDefinitions($base_plugin_definition)
    {
        $storage = $this->entityTypeManager
            ->getStorage('wmcontent_container');

        /** @var WmContentContainerInterface $container */
        foreach ($storage->loadMultiple() as $container) {
            $config = $container->getConfig();

            $wmcontentRouteName = 'entity.' . $config['host_entity_type'] . '.wmcontent_overview';
            $baseRouteName = 'entity.' . $config['host_entity_type'] . '.canonical';

            $this->derivatives[$wmcontentRouteName . '.' . $config['id']] = [
                'entity_type' => $config['host_entity_type'],
                'title' => $config['label'],
                'route_name' => $wmcontentRouteName,
                'route_parameters' => [
                    'container' => $config['id'],
                ],
                'base_route' => $baseRouteName,
                'cache' => false,
            ] + $base_plugin_definition;
        }

        return parent::getDerivativeDefinitions($base_plugin_definition);
    }
}
