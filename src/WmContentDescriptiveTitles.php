<?php

namespace Drupal\wmcontent;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\field\Entity\FieldConfig;
use Drupal\wmcontent\Entity\EntityTypeBundleInfo;
use Drupal\wmcontent\Event\WmContentEntityLabelEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WmContentDescriptiveTitles
{
    use StringTranslationTrait;

    /** @var CurrentRouteMatch */
    protected $currentRouteMatch;

    /** @var EntityTypeBundleInfo */
    protected $entityTypeBundleInfo;

    /** @var EntityTypeManager */
    protected $entityTypeManager;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * WmContentDescriptiveTitles constructor.
     * @param CurrentRouteMatch $currentRouteMatch
     * @param EntityTypeBundleInfo $entityTypeBundleInfo
     * @param EntityTypeManager $entityTypeManager
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        CurrentRouteMatch $currentRouteMatch,
        EntityTypeBundleInfo $entityTypeBundleInfo,
        EntityTypeManager $entityTypeManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->currentRouteMatch = $currentRouteMatch;
        $this->entityTypeBundleInfo = $entityTypeBundleInfo;
        $this->entityTypeManager = $entityTypeManager;
        $this->eventDispatcher = $eventDispatcher;
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
        $hostType = $this->currentRouteMatch->getParameter('host_type_id');
        $host = $this->currentRouteMatch->getParameter($hostType);

        if ($childId = $this->currentRouteMatch->getParameter('child_id')) {
            $child = $this
                ->entityTypeManager
                ->getStorage($container)
                ->load($childId);

            $bundle = $child->bundle();
        } else {
            // Get bundle from its parameter
            $bundle = $this->currentRouteMatch->getParameter('bundle');
        }

        // Build title
        $host = $host->label() ?: $bundleInfo[$hostType][$host->bundle()]['label'];
        $type = $bundleInfo[$container][$bundle]['label'];

        $routeName = $this->currentRouteMatch->getRouteName();
        switch (true) {
            case strpos($routeName, 'wmcontent_add') !== false:
                return $this->t(
                    'Add new %type to %host',
                    [
                        '%type' => $type,
                        '%host' => $host,
                    ]
                );
            case strpos($routeName, 'wmcontent_edit') !== false:
                return $this->t(
                    'Edit %type from %host',
                    [
                        '%type' => $type,
                        '%host' => $host,
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

        $event = new WmContentEntityLabelEvent($entity, $field, $fieldConfig);
        $this->eventDispatcher->dispatch(WmContentEntityLabelEvent::NAME, $event);
        if ($event->getLabel()) {
            return $event->getLabel();
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
