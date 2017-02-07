<?php

namespace Drupal\wmcontent\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\wmcontent\Entity\WmContentContainer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Provides dynamic local tasks for wmcontent.
 */
class WmContentLocalTasks extends DeriverBase implements ContainerDeriverInterface
{
    use StringTranslationTrait;

    /**
     * The base plugin ID
     *
     * @var string
     */
    protected $basePluginId;

    /**
     * The entity manager.
     *
     * @var \Drupal\Core\Entity\EntityManagerInterface
     */
    protected $entityTypeManager;


    /**
     * WmContentLocalTasks constructor.
     *
     * @param $base_plugin_id
     * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
     * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
     */
    public function __construct(
        $base_plugin_id,
        EntityTypeManager $entityTypeManager,
        TranslationInterface $stringTranslation
    ) {
        $this->basePluginId = $base_plugin_id;
        $this->entityManager = $entityTypeManager;
        $this->stringTranslation = $stringTranslation;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, $base_plugin_id)
    {
        /** @var EntityTypeManager $entityTypeManager */
        $entityTypeManager = $container->get('entity_type.manager');
        /** @var TranslationInterface $stringTranslation */
        $stringTranslation = $container->get('string_translation');

        return new static(
            $base_plugin_id,
            $entityTypeManager,
            $stringTranslation
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDerivativeDefinitions($base_plugin_definition)
    {
        // Load all config.
        $storage = $this->entityTypeManager->getStorage('wmcontent_container');

        /** @var WmContentContainer $container */
        foreach ($storage->loadMultiple() as $container) {
            $config = $container->getConfig();

            // Get the route name for the wmcontent overview.
            $wmcontent_route_name = 'entity.' . $config['host_entity_type'] . '.wmcontent_overview';

            $base_route_name = "entity." . $config['host_entity_type'] . ".canonical";

            $this->derivatives[$wmcontent_route_name . '.' . $config['id']] = array(
                'entity_type' => $config['host_entity_type'],
                'title' => $config['label'],
                'route_name' => $wmcontent_route_name,
                'route_parameters' => [
                    'container' => $config['id'],
                ],
                'base_route' => $base_route_name,
                'cache' => false,
            ) + $base_plugin_definition;
        }

        return parent::getDerivativeDefinitions($base_plugin_definition);
    }
}
