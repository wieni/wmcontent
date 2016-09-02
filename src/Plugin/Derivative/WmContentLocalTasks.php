<?php

namespace Drupal\wmcontent\Plugin\Derivative;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
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
    protected $entityManager;

    /**
     * Constructs a new ContentTranslationLocalTasks.
     *
     * @param string $base_plugin_id
     *   The base plugin ID.
     * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
     *   The content translation manager.
     * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
     *   The translation manager.
     */
    public function __construct(
        $base_plugin_id,
        EntityManagerInterface $entity_manager,
        TranslationInterface $string_translation
    ) {
        $this->basePluginId = $base_plugin_id;
        $this->entityManager = $entity_manager;
        $this->stringTranslation = $string_translation;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, $base_plugin_id)
    {
        return new static(
            $base_plugin_id,
            $container->get('entity.manager'),
            $container->get('string_translation')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDerivativeDefinitions($base_plugin_definition)
    {
        // Load all config.
        $storage = $this->entityManager->getStorage('wmcontent_container');

        foreach ($storage->loadMultiple() as $container) {
            $config = $container->getConfig();

            // Get the route name for the wmcontent overview.
            $translation_route_name = 'entity.' . $config['host_entity_type'] . '.wmcontent_overview';

            $base_route_name = "entity." . $config['host_entity_type'] . ".canonical";

            $this->derivatives[$translation_route_name . '.' . $config['id']] = array(
                'entity_type' => $config['host_entity_type'],
                'title' => $config['label'],
                'route_name' => $translation_route_name,
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
