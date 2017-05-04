<?php

namespace Drupal\wmcontent;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\field\Entity\FieldConfig;
use Drupal\wmcontent\Entity\EntityTypeBundleInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WmContentDescriptiveTitles implements ContainerInjectionInterface
{
    use StringTranslationTrait;

    /** @var CurrentRouteMatch */
    protected $currentRouteMatch;

    /** @var EntityTypeBundleInfo */
    protected $entityTypeBundleInfo;

    /** @var EntityTypeManager */
    protected $entityTypeManager;

    /**
     * WmContentDescriptiveTitles constructor.
     * @param CurrentRouteMatch $currentRouteMatch
     * @param EntityTypeBundleInfo $entityTypeBundleInfo
     * @param EntityTypeManager $entityTypeManager
     */
    public function __construct(
        CurrentRouteMatch $currentRouteMatch,
        EntityTypeBundleInfo $entityTypeBundleInfo,
        EntityTypeManager $entityTypeManager
    ) {
        $this->currentRouteMatch = $currentRouteMatch;
        $this->entityTypeBundleInfo = $entityTypeBundleInfo;
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        /** @var CurrentRouteMatch $currentRouteMatch */
        $currentRouteMatch = $container->get('current_route_match');

        /** @var EntityTypeBundleInfo $entityTypeBundleInfo */
        $entityTypeBundleInfo = $container->get('wmcontent.entity_type.bundle.info');

        /** @var EntityTypeManager $entityTypeManager */
        $entityTypeManager = $container->get('entity_type.manager');

        return new static(
            $currentRouteMatch,
            $entityTypeBundleInfo,
            $entityTypeManager
        );
    }

    /**
     * More descriptive 'Add more' button when adding referenced entities to a content block
     * @param array $form
     * @param ContentEntityBase $entity
     */
    public function updateAddMoreButtonTitle(array &$form, ContentEntityBase $entity)
    {
        foreach ($this->getFormFields($form) as $field) {
            if (!isset($form[$field]['widget']['add_more'])) {
                continue;
            }

            if ($bundleLabel = $this->getBundleLabel($entity, $field)) {
                $form[$field]['widget']['add_more']['#value'] =
                    new TranslatableMarkup(sprintf('Add another %s', $bundleLabel));
            }
        }
    }

    /**
     * More descriptive 'Create subcontent' button when adding more subcontent
     * @param array $form
     * @param ContentEntityBase $entity
     */
    public function updateAddAnotherSubContentButtonTitle(array &$form, ContentEntityBase $entity)
    {
        foreach ($this->getFormFields($form) as $field) {
            if (!isset($form[$field]['widget']['actions']['ief_add'])) {
                continue;
            }

            if ($bundleLabel = $this->getBundleLabel($entity, $field)) {
                $form[$field]['widget']['actions']['ief_add']['#value'] =
                    new TranslatableMarkup(sprintf('Add another %s', $bundleLabel));
            }
        }
    }

    /**
     * @return mixed
     */
    private function getContainer()
    {
        $containers = $this
            ->entityTypeManager
            ->getStorage('wmcontent_container')
            ->loadByProperties(['id' => $this->currentRouteMatch->getParameter('container')]);
        return reset($containers);
    }

    /**
     * @return string
     */
    private function getContainerType()
    {
        return $this->getContainer()->getChildEntityType();
    }

    /**
     * @param array $form
     * @return array
     */
    private function getFormFields($form)
    {
        return Element::children($form);
    }

    /**
     * @return TranslatableMarkup
     */
    public function getPageTitle()
    {
        $bundleInfo = $this->entityTypeBundleInfo->getAllBundleInfo();
        $container = $this->getContainerType();
        $node = $this->currentRouteMatch->getParameter('node');

        if ($childId = $this->currentRouteMatch->getParameter('child_id')) {
            $child = $this
                ->entityTypeManager
                ->getStorage($container)
                ->load($childId);

            $bundle = $child->bundle();
            $label = $child->label();

        } else {
            // Get bundle from its parameter
            $bundle = $this->currentRouteMatch->getParameter('bundle');
        }

        // Build title
        $node = $node->title->value;
        $type = $bundleInfo[$container][$bundle]['label'];
        $label = empty($label) ? $type : $label;

        switch ($this->currentRouteMatch->getRouteName()) {
            case 'entity.node.wmcontent_add':
                return $this->t(
                    'Add new %type to %node',
                    [
                        '%type' => $type,
                        '%node' => $node,
                    ]
                );

            case 'entity.node.wmcontent_edit':
                return $this->t(
                    'Edit %label from %node',
                    [
                        '%label' => $label,
                        '%node' => $node,
                    ]
                );

            default:
                return new TranslatableMarkup('');
        }
    }

    /**
     * @param $entity
     * @param $field
     * @return bool|string
     */
    private function getBundleLabel($entity, $field)
    {
        $bundleInfo = $this->entityTypeBundleInfo->getAllBundleInfo();
        $container = $this->getContainerType();
        $fieldConfig = $entity->getFieldDefinitions()[$field];
        $settings = $fieldConfig->getSetting('handler_settings');
        $bundleNames = $settings['target_bundles'] ?? [$fieldConfig->getTargetBundle()];
        $bundleLabel = 'item';

        if (empty($bundleNames) || !($fieldConfig instanceof FieldConfig) || $fieldConfig->get('entity_type') !== $container) {
            return false;
        }

        if (count($bundleNames) === 1) {
            if ($fieldConfig->getFieldStorageDefinition()->getType() === 'entity_reference') {
                $targetType = $fieldConfig->getFieldStorageDefinition()->getSetting('target_type');
            } else {
                $targetType = $fieldConfig->get('entity_type');
            }

            $bundleLabel = $bundleInfo[$targetType][array_values($bundleNames)[0]]['label'];
        }

        return $bundleLabel;
    }
}
