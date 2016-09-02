<?php

namespace Drupal\wmcontent\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic contextual links for wmcontent.
 *
 * @see \Drupal\wmcontent\Plugin\Menu\ContextualLink\WmContentContextualLinks
 */
class WmContentContextualLinks extends DeriverBase implements ContainerDeriverInterface
{

   /**
     * The entity manager.
     *
     * @var \Drupal\Core\Entity\EntityManagerInterface
     */
    protected $entityManager;

    /**
     * Constructs a new WmContentContextualLinks.
     *
     * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
     *   The entity manager.
     */
    public function __construct(EntityManagerInterface $entity_manager)
    {
        $this->entityManager = $entity_manager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, $base_plugin_id)
    {
        return new static(
            $container->get('entity.manager')
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

            $key = $config['host_entity_type'] . '.' . $config['id']

            $this->derivatives[$key]['title'] = $config['label'];
            $this->derivatives[$key]['title']['route_name'] = 'entity.' . $config['host_entity_type'] . '.wmcontent_overview';
            $this->derivatives[$key]['title']['group'] = ['host_entity_type'];
        }

        return parent::getDerivativeDefinitions($base_plugin_definition);
    }
}
