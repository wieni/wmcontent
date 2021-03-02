<?php

namespace Drupal\wmcontent\Form\Snapshot;

use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

abstract class SnapshotFormBase extends FormBase
{
    public const MODAL_DIALOG_OPTIONS = [
        'width' => 950,
        'minHeight' => 400,
    ];
    public const MODAL_MSG_CLASS = 'modal_message_container';

    use ModalAjaxProxyTrait;

    /** @var \Drupal\wmcontent\WmContentContainerInterface */
    protected $container;
    /** @var EntityInterface */
    protected $host;

    public function buildForm(
        array $form,
        FormStateInterface $form_state
    ) {
        $this->storeModalData($form, $form_state);
        $form['#prefix'] = '<div id="' . $this->getFormId() . '">';
        $form['#suffix'] = '</div>';

        if (!$this->isAjax()) {
            return $form;
        }

        // In a modal Drupal doesn't load the usual blocks. So we add our own
        static::addModalDrupalBlocks($form);

        return $form;
    }

    public function ajax(array $form, FormStateInterface $form_state)
    {
        if (
            !$form_state->isSubmitted()
            || $form_state->hasAnyErrors()
        ) {
            return $this->onAjaxError($form, $form_state);
        }

        // In ajax calls Drupal disables formstate redirects
        // So re-enable so we can fetch the target location
        $form_state->disableRedirect(false);

        $response = $this->onAjax($form, $form_state);

        // And re-disable so Drupal doesn't freak out
        $form_state->disableRedirect(true);

        return $response;
    }

    public static function addModalDrupalBlocks(array &$form): void
    {
        $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
        $form['#attached']['library'][] = 'core/jquery.form';

        // Add a message container that we can fill with an ajax MessageCommand
        $form['msg'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => [static::MODAL_MSG_CLASS],
            ],
        ];

        // Add the local actions block
        $form['local_actions'] = [
            '#theme' => 'block__local_actions_block',
            '#configuration' => [
                'provider' => 'wmcustom',
            ],
            '#plugin_id' => 'foo:bar',
            '#base_plugin_id' => 'foo:bar', /** @see \template_preprocess_block() */
            '#derivative_plugin_id' => 'foo:bar', /** @see \template_preprocess_block() */
            'content' => static::getLocalActions(),
        ];
    }

    protected function createSubmitButton(string $value): array
    {
        $button = [
            '#type' => 'submit',
            '#value' => $this->t($value),
            '#ajax' => [
                'callback' => '::ajax',
                'wrapper' => $this->getFormId(),
            ],
        ];
        if (!$this->isAjax()) {
            unset($button['#ajax']);
        }
        return $button;
    }

    protected function createCancelButton(string $title): array
    {
        $button = [
            '#type' => 'link',
            '#url' => $this->createCancelUrl(),
            '#title' => $this->t($title),
            '#attributes' => [
                'class' => ['use-ajax'],
                'data-dialog-type' => 'modal',
                'data-dialog-options' => json_encode(
                    static::MODAL_DIALOG_OPTIONS
                ),
            ],
        ];

        if (!$this->isAjax()) {
            unset($button['#attributes']);
        }
        return $button;
    }

    protected function createCancelUrl(): Url
    {
        return Url::fromRoute(
            "entity.{$this->host->getEntityTypeId()}.wmcontent_snapshot.overview",
            [
                $this->host->getEntityTypeId() => $this->host->id(),
                'container' => $this->container->id(),
            ]
        );
    }

    /** @return \Drupal\Core\Ajax\AjaxResponse|array */
    protected function onAjax(array $form, FormStateInterface $formState)
    {
        return $form;
    }

    /** @return \Drupal\Core\Ajax\AjaxResponse|array */
    protected function onAjaxError(array $form, FormStateInterface $formState)
    {
        return $form;
    }

    protected function messageCommand(string $message, string $type = 'status'): MessageCommand
    {
        return new MessageCommand(
            $message,
            sprintf('.%s', static::MODAL_MSG_CLASS),
            [
                'type' => $type,
            ]
        );
    }

    private static function getLocalActions()
    {
        // sue me
        $localActions = \Drupal::getContainer()->get('plugin.manager.menu.local_action');
        $routeName = \Drupal::getContainer()->get('current_route_match');

        if (!$localActions || !$routeName) {
            return [];
        }

        return $localActions->getActionsForRoute($routeName->getRouteName());
    }
}
