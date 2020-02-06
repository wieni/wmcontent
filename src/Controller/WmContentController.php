<?php

namespace Drupal\wmcontent\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\wmcontent\Form\WmContentMasterForm;
use Drupal\wmcontent\WmContentContainerInterface;
use Drupal\wmcontent\WmContentManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class WmContentController implements ContainerInjectionInterface
{
    use StringTranslationTrait;

    /** @var EntityTypeBundleInfoInterface */
    protected $entityTypeBundleInfo;
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var EntityFormBuilderInterface */
    protected $entityFormBuilder;
    /** @var FormBuilderInterface */
    protected $formBuilder;
    /** @var MessengerInterface */
    protected $messenger;
    /** @var WmContentManager */
    protected $wmContentManager;

    public function __construct(
        EntityTypeBundleInfoInterface $entityTypeBundleInfo,
        EntityTypeManagerInterface $entityTypeManager,
        EntityFormBuilderInterface $entityFormBuilder,
        FormBuilderInterface $formBuilder,
        MessengerInterface $messenger,
        WmContentManager $wmContentManager
    ) {
        $this->entityTypeBundleInfo = $entityTypeBundleInfo;
        $this->entityTypeManager = $entityTypeManager;
        $this->entityFormBuilder = $entityFormBuilder;
        $this->formBuilder = $formBuilder;
        $this->messenger = $messenger;
        $this->wmContentManager = $wmContentManager;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('entity_type.bundle.info'),
            $container->get('entity_type.manager'),
            $container->get('entity.form_builder'),
            $container->get('form_builder'),
            $container->get('messenger'),
            $container->get('wmcontent.manager')
        );
    }

    public function overview(WmContentContainerInterface $container, RouteMatchInterface $routeMatch, ?string $host_type_id = null)
    {
        $hostEntity = $routeMatch->getParameter($host_type_id);

        return [
            'form' => $this->formBuilder->getForm(WmContentMasterForm::class, $hostEntity, $container),
            '#title' => $this->t(
                '%slug for %label',
                [
                    '%slug' => $container->getLabel(),
                    '%label' => $hostEntity->label(),
                ],
            ),
        ];
    }

    public function add(WmContentContainerInterface $container, string $bundle, RouteMatchInterface $route, string $host_type_id)
    {
        $host = $route->getParameter($host_type_id);
        $blocks = $this->wmContentManager
            ->getContent($host, $container->id());
        $weight = 0;

        foreach ($blocks as $block) {
            if (!$block->hasField('wmcontent_weight')) {
                continue;
            }

            $blockWeight = $block->get('wmcontent_weight')->getString();
            $weight = $blockWeight > $weight ? $blockWeight : $weight;
        }

        $child = $this->entityTypeManager
            ->getStorage($container->getChildEntityType())
            ->create([
                'type' => $bundle,
                'langcode' => $host->get('langcode')->value,
                'wmcontent_parent' => $host->id(),
                'wmcontent_parent_type' => $host_type_id,
                'wmcontent_weight' => $weight + 1,
                'wmcontent_container' => $container->getId(),
            ]);

        $form = $this->entityFormBuilder->getForm($child);
        $form['wmcontent_container']['#access'] = false;
        $form['wmcontent_parent_type']['#access'] = false;
        $form['wmcontent_parent']['#access'] = false;
        $form['wmcontent_weight']['#access'] = false;

        return $form;
    }

    public function addTitle(RouteMatchInterface $routeMatch, WmContentContainerInterface $container, string $bundle)
    {
        $bundleInfo = $this->entityTypeBundleInfo->getAllBundleInfo();

        $type = $bundleInfo[$container->getChildEntityType()][$bundle]['label'];
        $host = $routeMatch->getParameter($container->getHostEntityType())->label();

        return $this->t(
            'Add new %type to %host',
            [
                '%type' => $type,
                '%host' => $host,
            ]
        );
    }

    public function delete(WmContentContainerInterface $container, EntityInterface $child, RouteMatchInterface $routeMatch, ?string $host_type_id = null)
    {
        $host = $routeMatch->getParameter($host_type_id);

        $child->delete();

        $this->messenger->addStatus(
            $this->t(
                '%container_label %name has been deleted.',
                [
                    '%container_label' => $container->getLabel(),
                    '%name' => $child->label(),
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

    public function edit(EntityInterface $child)
    {
        $form = $this->entityFormBuilder->getForm($child);
        $form['wmcontent_container']['#access'] = false;
        $form['wmcontent_parent_type']['#access'] = false;
        $form['wmcontent_parent']['#access'] = false;
        $form['wmcontent_weight']['#access'] = false;

        return $form;
    }

    public function editTitle(RouteMatchInterface $routeMatch, WmContentContainerInterface $container, EntityInterface $child)
    {
        $bundleInfo = $this->entityTypeBundleInfo->getAllBundleInfo();

        $type = $bundleInfo[$container->getChildEntityType()][$child->bundle()]['label'];
        $host = $routeMatch->getParameter($container->getHostEntityType())->label();

        return $this->t(
            'Edit %type from %host',
            [
                '%type' => $type,
                '%host' => $host,
            ]
        );
    }
}
