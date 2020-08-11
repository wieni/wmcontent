<?php

namespace Drupal\wmcontent\Form\Snapshot;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\wmcontent\WmContentContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SnapshotImportForm extends SnapshotFormBase
{
    /** @var \Drupal\wmcontent\Service\Snapshot\SnapshotService */
    protected $snapshotService;

    public static function create(ContainerInterface $container)
    {
        /** @var static $form */
        $form = parent::create($container);
        $form->snapshotService = $container->get('wmcontent.snapshot');

        return $form;
    }

    public function getFormId()
    {
        return 'wmcontent_snapshot_import';
    }

    public function buildForm(
        array $form,
        FormStateInterface $form_state,
        ?WmContentContainerInterface $contentContainer = null,
        ?EntityInterface $host = null
    ) {
        if (!$host || !$contentContainer) {
            return $form;
        }
        $this->container = $contentContainer;
        $this->host = $host;

        $form = parent::buildForm($form, $form_state);

        $form['blob'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Import code'),
            '#required' => true,
            '#rows' => 4,
        ];

        $form['actions']['submit'] = $this->createSubmitButton('Import');
        $form['actions']['cancel'] = $this->createCancelButton('Back');

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $input = $form_state->getUserInput();

        try {
            $this->snapshotService->import($input['blob']);
        } catch (\Exception $e) {
            $form_state->setErrorByName('blob', $e->getMessage());
        }

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $input = $form_state->getUserInput();

        $snapshot = $this->snapshotService->import($input['blob']);
        $snapshot->setHost($this->host);
        $snapshot->setContainer($this->container);
        $snapshot->save();

        if (!$this->isAjax()) { // If it's ajax we'll show the message with a MessageCommand
            $this->messenger()->addStatus('Imported snapshot');
        }

        $form_state->setRedirect(
            "entity.{$this->host->getEntityTypeId()}.wmcontent_snapshot.overview",
            [
                $this->host->getEntityTypeId() => $this->host->id(),
                'container' => $this->container->id(),
            ]
        );

        return $form;
    }

    /**
     * This is being called from the parent ::ajax handler
     * @see \Drupal\wmcontent\Form\Snapshot\SnapshotFormBase::ajax()
     */
    public function onAjax(array $form, FormStateInterface $formState)
    {
        // We want to redirect to the snapshot overview. But because we are in
        // a modal what we actually want to do instead is replace the modal with
        // the snapshot overview. So we create an ajax request to the target
        // location ( snapshot overview ) and forward the AjaxResponse back to
        // the client.
        $response = $this->doAjaxRequest(
            // Redirect is set in ::submit
            $formState->getRedirect()
        );
        if (!$response) {
            return $form;
        }
        $response->addCommand(
            $this->messageCommand('Snapshot imported')
        );

        return $response ?: $form;
    }
}
