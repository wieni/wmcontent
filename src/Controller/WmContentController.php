<?php

namespace Drupal\wmcontent\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityInterface;
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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WmContentController implements ContainerInjectionInterface
{
    use StringTranslationTrait;

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
        EntityTypeManagerInterface $entityTypeManager,
        EntityFormBuilderInterface $entityFormBuilder,
        FormBuilderInterface $formBuilder,
        MessengerInterface $messenger,
        WmContentManager $wmContentManager
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->entityFormBuilder = $entityFormBuilder;
        $this->formBuilder = $formBuilder;
        $this->messenger = $messenger;
        $this->wmContentManager = $wmContentManager;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('entity_type.manager'),
            $container->get('entity.form_builder'),
            $container->get('form_builder'),
            $container->get('messenger'),
            $container->get('wmcontent.manager'),
        );
    }

    public function overview(string $container, RouteMatchInterface $routeMatch, ?string $host_type_id = null)
    {
        $contentContainer = $this->entityTypeManager
            ->getStorage('wmcontent_container')
            ->load($container);

        if (!$contentContainer instanceof WmContentContainerInterface) {
            throw new NotFoundHttpException(
                $this->t('Container @container does not exist.', ['@container' => $container])
            );
        }

        $hostEntity = $routeMatch->getParameter($host_type_id);

        return [
            'form' => $this->formBuilder->getForm(WmContentMasterForm::class, $hostEntity, $contentContainer),
            '#title' => $this->t(
                '%slug for %label',
                [
                    '%slug' => $contentContainer->getLabel(),
                    '%label' => $hostEntity->label(),
                ],
            ),
        ];
    }

    public function add(string $container, string $bundle, RouteMatchInterface $route, string $host_type_id)
    {
        /** @var WmContentContainerInterface $currentContainer */
        $currentContainer = $this
            ->entityTypeManager
            ->getStorage('wmcontent_container')
            ->load($container);
        $host = $route->getParameter($host_type_id);

        $blocks = $this->wmContentManager
            ->getContent($host, $currentContainer->id());
        $weight = 0;

        foreach ($blocks as $block) {
            if (!$block->hasField('wmcontent_weight')) {
                continue;
            }

            $blockWeight = $block->get('wmcontent_weight')->getString();
            $weight = $blockWeight > $weight ? $blockWeight : $weight;
        }

        $child = $this->entityTypeManager
            ->getStorage($currentContainer->getChildEntityType())
            ->create([
                'type' => $bundle,
                'langcode' => $host->get('langcode')->value,
                'wmcontent_parent' => $host->id(),
                'wmcontent_parent_type' => $host_type_id,
                'wmcontent_weight' => $weight + 1,
                'wmcontent_container' => $currentContainer->getId(),
            ]);

        $form = $this->entityFormBuilder->getForm($child);
        $form['wmcontent_container']['#access'] = false;
        $form['wmcontent_parent_type']['#access'] = false;
        $form['wmcontent_parent']['#access'] = false;
        $form['wmcontent_weight']['#access'] = false;

        return $form;
    }

    public function delete(string $container, string $childId, RouteMatchInterface $routeMatch, ?string $host_type_id = null)
    {
        $current_container = $this->entityTypeManager
            ->getStorage('wmcontent_container')
            ->load($container);

        $host = $routeMatch->getParameter($host_type_id);

        $child = $this->entityTypeManager
            ->getStorage($current_container->getChildEntityType())
            ->load($childId);

        if (!$child instanceof EntityInterface) {
            throw new NotFoundHttpException;
        }

        $child->delete();

        $this->messenger->addStatus(
            $this->t(
                '%container_label %name has been deleted.',
                [
                    '%container_label' => $current_container->getLabel(),
                    '%name' => $child->label(),
                ]
            )
        );

        return new RedirectResponse(
            Url::fromRoute(
                'entity.' . $current_container->getHostEntityType() . '.wmcontent_overview',
                [
                    $current_container->getHostEntityType() => $host->id(),
                    'container' => $current_container->id(),
                ]
            )->toString()
        );
    }

    public function edit(string $container, string $child_id)
    {
        $current_container = $this->entityTypeManager
            ->getStorage('wmcontent_container')
            ->load($container);

        $child = $this->entityTypeManager
            ->getStorage($current_container->getChildEntityType())
            ->load($child_id);

        if (!$child instanceof EntityInterface) {
            throw new NotFoundHttpException;
        }

        $form = $this->entityFormBuilder->getForm($child);
        $form['wmcontent_container']['#access'] = false;
        $form['wmcontent_parent_type']['#access'] = false;
        $form['wmcontent_parent']['#access'] = false;
        $form['wmcontent_weight']['#access'] = false;

        return $form;
    }
}
