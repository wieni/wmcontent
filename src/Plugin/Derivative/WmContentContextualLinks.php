<?php

namespace Drupal\wmcontent\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\wmcontent\Entity\WmContentContainer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic contextual links for wmcontent.
 *
 * @see \Drupal\wmcontent\Plugin\Menu\ContextualLink\WmContentContextualLinks
 */
class WmContentContextualLinks extends DeriverBase implements ContainerDeriverInterface
{

    /** @var \Drupal\Core\Entity\EntityTypeManager */
    protected $entityTypeManager;


    /**
     * WmContentContextualLinks constructor.
     *
     * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
     */
    public function __construct(EntityTypeManager $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, $base_plugin_id)
    {
        /** @var EntityTypeManager $entityTypeManager */
        $entityTypeManager = $container->get('entity_type.manager');
        return new static(
            $entityTypeManager
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

            $key = $config['host_entity_type'] . '.' . $config['id'];

            $this->derivatives[$key]['title'] = $config['label'];
            $this->derivatives[$key]['title']['route_name'] = 'entity.' . $config['host_entity_type'] . '.wmcontent_overview';
            $this->derivatives[$key]['title']['group'] = ['host_entity_type'];
        }

        return parent::getDerivativeDefinitions($base_plugin_definition);
    }
}
