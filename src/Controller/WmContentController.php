<?php

namespace Drupal\wmcontent\Controller;

use Drupal\wmcontent\WmContentManagerInterface;
use Drupal\wmcontent\Form\WmContentMasterForm;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Base class for wmcontent controllers.
 */
class WmContentController extends ControllerBase
{

    /**
     * The wmcontent manager.
     *
     * @var \Drupal\wmcontent\WmContentManagerInterface
     */
    protected $wmContentManager;

    /**
     * The formbuilder.
     *
     * @var \Drupal\Core\Form\FormBuilderInterface
     */
    protected $formbuilder;

    /**
     * Initializes a wmcontent controller.
     *
     * @param \Drupal\wmcontent\WmContentManagerInterface $wmcontent_manager
     *   A wmcontent manager instance.
     */
    public function __construct(WmContentManagerInterface $wmcontent_manager, FormBuilderInterface $formbuilder)
    {
        $this->wmContentManager = $wmcontent_manager;
        $this->formbuilder = $formbuilder;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
          $container->get('wmcontent.manager'),
          $container->get('form_builder')
        );
    }

    /**
     * Builds the translations overview page.
     *
     * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
     *   The route match.
     * @param string $host_type_id
     *   Host type ID.
     *
     * @return array
     *   Array of page elements to render.
     */
    public function overview($container, RouteMatchInterface $route_match, $host_type_id = null)
    {

        $build = [];

        // Get the container.
        $current_container = $this->entityManager()->getStorage('wmcontent_container')->load($container);

        $host_entity = $route_match->getParameter($host_type_id);

        if ($current_container->getId()) {
            $form = new WmContentMasterForm(
                $this->wmContentManager,
                $host_entity,
                $current_container
            );

            $build['#title'] = $this->t(
                '%slug for %label',
                array(
                    '%slug' => $current_container->getLabel(),
                    '%label' => $host_entity->label(),
                )
            );


            $build['form'] = $this->formbuilder->getForm($form);
        } else {
            throw new NotFoundHttpException(
                $this->t('Container @container does not exist.', ['@container' => $container])
            );
        }

        return $build;
    }

    /**
     * Provides the entity submission form.
     *
     * @param string $typebundle
     *   The entity type bundle.
     * @param \Drupal\Core\Routing\RouteMatchInterface $route
     *   The route match object from which to extract the entity type.
     * @param string $entity_type_id
     *   (optional) The entity type ID.
     *
     * @return array
     *   The entity submission form.
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

        return $form;
    }

    /**
     * Function delete.
     *
     * Deletes a child entity and goes back to the content page.
     *
     * @param string $type
     *   The type of child.
     * @param int $child_id
     *   The child id.
     *
     * @return RedirectResponse
     *   Going to go back to the host content master editor.
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
     * Builds the edit translation page.
     *
     * @param string $type
     *   The type of child.
     * @param int $child_id
     *   The child id.
     *
     * @return array
     *   A processed form array ready to be rendered.
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

        // Get the form and return it.
        return $form;
    }
}
