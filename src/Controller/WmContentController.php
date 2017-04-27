<?php

namespace Drupal\wmcontent\Controller;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\wmcontent\Entity\WmContentContainer;
use Drupal\wmcontent\WmContentDescriptiveTitles;
use Drupal\wmcontent\WmContentManager;
use Drupal\wmcontent\Form\WmContentMasterForm;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Base class for wmcontent controllers.
 */
class WmContentController extends ControllerBase
{
    /** @var WmContentManager */
    protected $wmContentManager;

    /** @var FormBuilderInterface */
    protected $formBuilder;

    /** @var WmContentDescriptiveTitles */
    protected $descriptiveTitles;

    /**
     * WmContentController constructor.
     * @param WmContentManager $wmContentManager
     * @param FormBuilderInterface $formBuilder
     * @param WmContentDescriptiveTitles $descriptiveTitles
     */
    public function __construct(
        WmContentManager $wmContentManager,
        FormBuilderInterface $formBuilder,
        WmContentDescriptiveTitles $descriptiveTitles
    ) {
        $this->wmContentManager = $wmContentManager;
        $this->formBuilder = $formBuilder;
        $this->descriptiveTitles = $descriptiveTitles;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        /** @var WmContentManager $wmContentManager */
        $wmContentManager = $container->get('wmcontent.manager');
        /** @var FormBuilderInterface $formBuilder */
        $formBuilder = $container->get('form_builder');
        /** @var WmContentDescriptiveTitles */
        $descriptiveTitles = $container->get('wmcontent.descriptive_titles');

        return new static(
            $wmContentManager,
            $formBuilder,
            $descriptiveTitles
        );
    }


    /**
     * @param string $container
     * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
     * @param null $host_type_id
     *
     * @return array
     */
    public function overview(string $container, RouteMatchInterface $route_match, $host_type_id = null)
    {
        $build = [];
        // Get the container.
        /** @var WmContentContainer $current_container */
        $current_container = $this->entityTypeManager()->getStorage('wmcontent_container')->load($container);
        $host_entity = $route_match->getParameter($host_type_id);

        if ($current_container->getId()) {
            // Start a form.
            $form = new WmContentMasterForm(
                $this->wmContentManager,
                $host_entity,
                $current_container
            );
            $build['#title'] = $this->t(
                '%slug for %label',
                [
                    '%slug' => $current_container->getLabel(),
                    '%label' => $host_entity->label(),
                ]
            );
            $build['form'] = $this->formBuilder->getForm($form);
        } else {
            throw new NotFoundHttpException(
                $this->t('Container @container does not exist.', ['@container' => $container])
            );
        }
        return $build;
    }


    /**
     * @param $container
     * @param $bundle
     * @param \Drupal\Core\Routing\RouteMatchInterface $route
     * @param $host_type_id
     *
     * @return array
     */
    public function add($container, $bundle, RouteMatchInterface $route, $host_type_id)
    {
        // Get the container.
        $current_container = $this
            ->entityTypeManager()
            ->getStorage('wmcontent_container')
            ->load($container);

        $host = $route->getParameter($host_type_id);

        // Create an empty entity of the chosen entity type and the bundle.
        $child = $this
            ->entityTypeManager()
            ->getStorage($current_container->getChildEntityType())
            ->create(
                array(
                    'type' => $bundle,
                )
            );

        // Get the id of the parent and add it in.
        $child->set('wmcontent_parent', $host->id());
        $child->set('wmcontent_parent_type', $host_type_id);
        $child->set('wmcontent_size', 'full');
        $child->set('wmcontent_alignment', 'left');
        $child->set('wmcontent_weight', 50);
        $child->set('wmcontent_container', $current_container->getId());

        // In the correct language.
        $child->set('langcode', $host->get('langcode')->value);

        // Get the form.
        $form = $this->entityFormBuilder()->getForm($child);

        // Hide some stuff.
        $form['wmcontent_container']['#access'] = false;
        $form['wmcontent_parent_type']['#access'] = false;
        $form['wmcontent_parent']['#access'] = false;
        $form['wmcontent_weight']['#access'] = false;

        // Change the 'Add another item' button label
        $this->descriptiveTitles->updateAddMoreButtonTitle($form, $child);
        $this->descriptiveTitles->updateAddAnotherSubContentButtonTitle($form, $child);

        return $form;
    }


    /**
     * @param $container
     * @param $child_id
     * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
     * @param null $host_type_id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delete($container, $child_id, RouteMatchInterface $route_match, $host_type_id = null)
    {
        // Get the container.
        $current_container = $this
            ->entityTypeManager()
            ->getStorage('wmcontent_container')
            ->load($container);

        $host = $route_match->getParameter($host_type_id);

        // Load up the child.
        $child = $this
            ->entityTypeManager()
            ->getStorage($current_container->getChildEntityType())
            ->load($child_id);

        $child->delete();

        drupal_set_message(
            $this->t(
                '%container_label %name has been deleted.',
                [
                    '%container_label' => $current_container->getLabel(),
                    '%name' => $child->label(),
                ]
            )
        );

        return $this->redirect(
            'entity.' . $current_container->getHostEntityType() . '.wmcontent_overview',
            [
                $current_container->getHostEntityType() => $host->id(),
                'container' => $current_container->id(),
            ]
        );
    }


    /**
     * @param $container
     * @param $child_id
     *
     * @return array
     */
    public function edit($container, $child_id)
    {
        // Get the container.
        $current_container = $this
            ->entityTypeManager()
            ->getStorage('wmcontent_container')
            ->load($container);

        // Load up the child.
        $child = $this
            ->entityTypeManager()
            ->getStorage($current_container->getChildEntityType())
            ->load($child_id);

        // Get the form.
        $form = $this->entityFormBuilder()->getForm($child);

        // Hide some stuff.
        $form['wmcontent_container']['#access'] = false;
        $form['wmcontent_parent_type']['#access'] = false;
        $form['wmcontent_parent']['#access'] = false;
        $form['wmcontent_weight']['#access'] = false;

        // Change the 'Add another item' button label
        $this->descriptiveTitles->updateAddMoreButtonTitle($form, $child);
        $this->descriptiveTitles->updateAddAnotherSubContentButtonTitle($form, $child);

        // Get the form and return it.
        return $form;
    }
}
