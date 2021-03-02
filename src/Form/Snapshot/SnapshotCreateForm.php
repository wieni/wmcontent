<?php

namespace Drupal\wmcontent\Form\Snapshot;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\wmcontent\Entity\Snapshot;
use Drupal\wmcontent\WmContentContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SnapshotCreateForm extends SnapshotFormBase
{
    /** @var \Drupal\wmcontent\WmContentManagerInterface */
    protected $contentManager;
    /** @var \Drupal\wmcontent\Service\Snapshot\SnapshotService */
    protected $snapshotService;
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface */
    protected $entityTypeManager;

    public static function create(ContainerInterface $container)
    {
        /** @var static $form */
        $form = parent::create($container);
        $form->contentManager = $container->get('wmcontent.manager');
        $form->snapshotService = $container->get('wmcontent.snapshot');
        $form->entityTypeManager = $container->get('entity_type.manager');

        return $form;
    }

    public function getFormId()
    {
        return 'wmcontent_snapshot_create';
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

        $input = $form_state->getUserInput();

        $form['#prefix'] = '<div id="' . $this->getFormId() . '">';
        $form['#suffix'] = '</div>';

        $form['title'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Title'),
            '#required' => true,
            '#description' => $this->t('A descriptive name for this snapshot.'),
        ];
        $form['comment'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Description'),
            '#description' => $this->t('A good description of what\'s in this snapshot.'),
            '#rows' => 4,
        ];
        $form['actions']['submit'] = $this->createSubmitButton('Submit');
        $form['actions']['cancel'] = $this->createCancelButton('Cancel');

        $form['table'] = [
            '#type' => 'table',
            '#header' => [
                '',
                'Type',
                'Label',
            ],
            '#rows' => [],
            '#empty' => $this->t('There are no content blocks yet.'),
        ];

        foreach ($this->contentManager->getContent($host, $contentContainer->getId()) as $block) {
            $form['table']['#rows'][$block->id()] = [
                'enabled' => [
                    'data' => [
                        '#type' => 'checkbox',
                        '#name' => 'blocks[' . $block->id() . ']',
                        '#return_value' => $block->id(),
                        '#checked' => isset($input['blocks'])
                            ? !empty($input['blocks'][$block->id()])
                            : true,
                    ],
                ],
                'type' => [
                    'data' => [
                        '#markup' => $block->type->entity->label(), // is bundle
                    ],
                ],
                'label' => [
                    'data' => [
                        '#markup' => $block->label(),
                    ],
                ],
            ];

            if (!$this->snapshotService->isSnapshotable($block)) {
                $form['table']['#rows'][$block->id()]['enabled']['data']['#attributes']['disabled'] = 'disabled';
                $form['table']['#rows'][$block->id()]['enabled']['data']['#checked'] = false;
            }
        }

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $input = $form_state->getUserInput();

        $title = $input['title'];
        $description = $input['comment'];

        $blockIds = array_keys(array_filter($input['blocks']));

        $blocks = $this->entityTypeManager->getStorage(
            $this->container->getChildEntityType()
        )->loadMultiple($blockIds);

        /** @var \Drupal\user\UserInterface $user */
        $user = $this->entityTypeManager
            ->getStorage('user')
            ->load($this->currentUser()->id());

        /** @var Snapshot $snapshot */
        $snapshot = $this->snapshotService->createSnapshot(
            $blocks,
            $title,
            $description,
            $user,
            $this->container,
            $this->host,
            null
        );
        $snapshot->save();

        if (!$this->isAjax()) { // If ajax we use a MessageCommand in ::onAjax
            $this->messenger()->addStatus('Snapshot created.');
        }

        $form_state->setRedirect(
            "entity.{$this->host->getEntityTypeId()}.wmcontent_overview",
            [
                $this->host->getEntityTypeId() => $this->host->id(),
                'container' => $this->container->id(),
            ]
        );

        return $form;
    }

    public function onAjax(array $form, FormStateInterface $formState)
    {
        $response = new AjaxResponse();

        $response->addCommand(
            // We don't call $this->messageCommand() because we want to display
            // this in the real messages div and not in the one of the modal.
            new MessageCommand('Snapshot created.')
        );
        $response->addCommand(
            new CloseModalDialogCommand()
        );

        return $response;
    }
}
