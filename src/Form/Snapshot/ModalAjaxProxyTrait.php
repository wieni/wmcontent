<?php

namespace Drupal\wmcontent\Form\Snapshot;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\EventSubscriber\AjaxResponseSubscriber;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

trait ModalAjaxProxyTrait
{
    private $modalData = [];

    /**
     * Make it possible to render any page as an ajax callback in a modal.
     *
     * We convert the passed Url to a modal request. Which Drupal picks up and
     * converts to a set of AjaxCommands that will do the necessary things to render
     * that page. This allows us to render overviews as an ajax-callback without
     * having to build the overview in the ajax callback itself.
     */
    protected function doAjaxRequest(Url $url): ?AjaxResponse
    {
        // We add _wrapper_format=drupal_modal so Drupal knows it has to respond
        // with an AjaxResponse. We also pass in the modalData we originally got
        // from our client. It contains info such as which libraries are already
        // loaded and the modal width etc.
        // We forward that data so Drupal knows it doesn't have to add a bunch of
        // unnecessary AddCssCommand or ReplaceCommand objects to the AjaxResponse.
        $request = Request::create(
            $url->mergeOptions(['query' => [
                MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_modal',
            ]])->toString(),
            'POST', // Default behaviour is to always POST. /shrug
            $this->modalData,
            $this->getRequestObject()->cookies->all()
        );

        try {
            $response = $this->getKernel()->handle($request, HttpKernelInterface::SUB_REQUEST, false);
        } catch (\Exception $e) {
            // todo: meh
            Error::logException(\Drupal::logger('wmcontent.modal'), $e);
        }

        if (!$response instanceof AjaxResponse || !$response->getStatusCode() === 200) {
            return null;
        }

        // For some weird reason I can't just take the AjaxResponse as-is and
        // return that. It causes the ajax commands to be wrapped in a
        // <textarea>. Very weird. But a shallow clone does the trick.
        // Todo: inspect this
        return $this->cloneResponse($response);
    }

    protected function storeModalData(array &$form, FormStateInterface $formState): void
    {
        // If the current form was created with an ajax request we want to store
        // the information related to that request ( dialogOptions and
        // ajax_page_state ).
        // If we decide to make a subrequest to render another page in a modal
        // we need to pass this information along so Drupal knows which libraries
        // are already loaded etc.
        if (
            $this->isAjax()
            && !$formState->has('modal_data')
        ) {
            $formState->set('modal_data', $this->getModalData($formState));
        }

        if ($formState->has('modal_data')) {
            $this->modalData = $formState->get('modal_data');
            $form['modal_data'] = [
                '#type' => 'hidden',
                '#value' => json_encode($this->modalData),
            ];
        }
    }

    protected function getModalData(FormStateInterface $formState): array
    {
        if ($formState->has('modal_data')) {
            return $formState->get('modal_data');
        }

        if (isset($formState->getUserInput()['modal_data'])) {
            return json_decode($formState->getUserInput()['modal_data'], true, 512);
        }

        $request = $this->getRequestObject();
        if (!$request || !$this->isModal($request)) {
            return [];
        }

        // Only keep the necessary data
        return array_intersect_key(
            $request->request->all(),
            [
                'js' => true,
                'dialogOptions' => true,
                AjaxResponseSubscriber::AJAX_REQUEST_PARAMETER => true, // _drupal_ajax
                'ajax_page_state' => true,
            ]
        );
    }

    protected function isAjax(): bool
    {
        $request = $this->getRequestObject();
        return $request
            && (
                $request->query->has(FormBuilder::AJAX_FORM_REQUEST)
                || $this->isModal($request)
            );
    }

    protected function cloneResponse(AjaxResponse $response): AjaxResponse
    {
        $cloned = new AjaxResponse();

        // Get the command-array by-reference
        $commands = &$cloned->getCommands();
        // And fill it with the commands of the original response
        $commands = $response->getCommands();

        // Also add the attachments of the original response
        $cloned->setAttachments($response->getAttachments());

        return $cloned;
    }

    private function isModal(Request $request): bool
    {
        return $request
            && $request->query->get(MainContentViewSubscriber::WRAPPER_FORMAT) === 'drupal_modal';
    }

    private function getRequestObject(): ?Request
    {
        // sue me
        return \Drupal::request();
    }

    private function getKernel(): HttpKernelInterface
    {
        // sue me
        return \Drupal::getContainer()->get('http_kernel');
    }
}
