<?php

namespace Drupal\wmcontent\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\wmcontent\WmContentContainerInterface;
use Drupal\wmcontent\WmContentManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class WmContentChildController implements ContainerInjectionInterface
{
    use StringTranslationTrait;

    /** @var EntityTypeBundleInfoInterface */
    protected $entityTypeBundleInfo;
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var EntityFormBuilderInterface */
    protected $entityFormBuilder;
    /** @var MessengerInterface */
    protected $messenger;
    /** @var AccountProxyInterface */
    protected $currentUser;
    /** @var WmContentManager */
    protected $wmContentManager;

    public static function create(ContainerInterface $container)
    {
        $instance = new static;
        $instance->entityTypeBundleInfo = $container->get('entity_type.bundle.info');
        $instance->entityTypeManager = $container->get('entity_type.manager');
        $instance->entityFormBuilder = $container->get('entity.form_builder');
        $instance->messenger = $container->get('messenger');
        $instance->currentUser = $container->get('current_user');
        $instance->wmContentManager = $container->get('wmcontent.manager');

        return $instance;
    }

    public function add(WmContentContainerInterface $container, string $bundle, ContentEntityInterface $host)
    {
        $child = $this->createChildEntity($container, $bundle, $host);

        $form = $this->entityFormBuilder->getForm($child);
        $form['wmcontent_container']['#access'] = false;
        $form['wmcontent_parent_type']['#access'] = false;
        $form['wmcontent_parent']['#access'] = false;
        $form['wmcontent_weight']['#access'] = false;

        return $form;
    }

    public function addTitle(WmContentContainerInterface $container, string $bundle, ContentEntityInterface $host)
    {
        $bundleInfo = $this->entityTypeBundleInfo->getAllBundleInfo();
        $type = $bundleInfo[$container->getChildEntityType()][$bundle]['label'];

        return $this->t(
            'Add new %type to %host',
            [
                '%type' => $type,
                '%host' => (string) $host->label(),
            ]
        );
    }

    public function delete(WmContentContainerInterface $container, ContentEntityInterface $child, ContentEntityInterface $host)
    {
        $child->delete();

        $this->messenger->addStatus(
            $this->t(
                '%container_label %name has been deleted.',
                [
                    '%container_label' => $container->getLabel(),
                    '%name' => (string) $child->label(),
                ]
            )
        );

        return new RedirectResponse(
            Url::fromRoute(
                'entity.' . $container->getHostEntityType() . '.wmcontent_overview',
                [
                    $container->getHostEntityType() => $host->id(),
                    'container' => $container->id(),
                ]
            )->toString()
        );
    }

    public function edit(ContentEntityInterface $child)
    {
        $form = $this->entityFormBuilder->getForm($child);
        $form['wmcontent_container']['#access'] = false;
        $form['wmcontent_parent_type']['#access'] = false;
        $form['wmcontent_parent']['#access'] = false;
        $form['wmcontent_weight']['#access'] = false;

        return $form;
    }

    public function editTitle(WmContentContainerInterface $container, ContentEntityInterface $child, ContentEntityInterface $host)
    {
        $bundleInfo = $this->entityTypeBundleInfo->getAllBundleInfo();
        $type = $bundleInfo[$container->getChildEntityType()][$child->bundle()]['label'];

        return $this->t(
            'Edit %type from %host',
            [
                '%type' => $type,
                '%host' => (string) $host->label(),
            ]
        );
    }

    protected function createChildEntity(WmContentContainerInterface $container, string $bundle, ContentEntityInterface $host): ContentEntityInterface
    {
        $blocks = $this->wmContentManager->getContent($host, $container->id());
        $entityType = $this->entityTypeManager->getDefinition($container->getChildEntityType());

        $weight = 0;
        foreach ($blocks as $block) {
            if (!$block->hasField('wmcontent_weight')) {
                continue;
            }

            $blockWeight = $block->get('wmcontent_weight')->getString();
            $weight = $blockWeight > $weight ? $blockWeight : $weight;
        }

        $values = [
            'wmcontent_parent' => $host->id(),
            'wmcontent_parent_type' => $host->getEntityTypeId(),
            'wmcontent_weight' => $weight + 1,
            'wmcontent_container' => $container->getId(),
        ];

        if ($bundleKey = $entityType->getKey('bundle')) {
            $values[$bundleKey] = $bundle;
        }

        if ($langcodeKey = $entityType->getKey('langcode')) {
            $values[$langcodeKey] = $host->language()->getId();
        }

        if ($uidKey = $entityType->getKey('uid')) {
            $values[$uidKey] = $this->currentUser->id();
        }

        if ($ownerKey = $entityType->getKey('owner')) {
            $values[$ownerKey] = $this->currentUser->id();
        }

        return $this->entityTypeManager
            ->getStorage($entityType->id())
            ->create($values);
    }
}
