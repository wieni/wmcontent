<?php

namespace Drupal\wmcontent\Form\Snapshot;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\wmcontent\Entity\Snapshot;
use Drupal\wmcontent\Entity\SnapshotLog;
use Drupal\wmcontent\WmContentContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SnapshotRestoreForm extends SnapshotFormBase
{
    /** @var \Drupal\wmcontent\WmContentManagerInterface */
    protected $contentManager;
    /** @var \Drupal\wmcontent\Service\Snapshot\SnapshotService */
    protected $snapshotService;
    /** @var \Drupal\Core\Database\Connection */
    protected $db;

    /** @var Snapshot */
    private $snapshot; // private so it isn't serialized

    public static function create(ContainerInterface $container)
    {
        /** @var static $form */
        $form = parent::create($container);
        $form->contentManager = $container->get('wmcontent.manager');
        $form->snapshotService = $container->get('wmcontent.snapshot');
        $form->db = $container->get('database');

        return $form;
    }

    public function getFormId()
    {
        return 'wmcontent_snapshot_restore';
    }

    public function buildForm(
        array $form,
        FormStateInterface $form_state,
        ?WmContentContainerInterface $contentContainer = null,
        ?EntityInterface $host = null,
        ?Snapshot $snapshot = null
    ) {
        if (!$host || !$contentContainer || !$snapshot) {
            return $form;
        }
        $this->container = $contentContainer;
        $this->host = $host;
        $this->snapshot = $snapshot;

        $form = parent::buildForm($form, $form_state);

        $input = $form_state->getUserInput();

        $form['snapshot_title'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Snapshot title'),
            '#default_value' => $snapshot->getTitle(),
            '#attributes' => [
                'disabled' => 'disabled',
            ],
        ];
        $form['description'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Snapshot description'),
            '#default_value' => $snapshot->getComment(),
            '#rows' => 4,
            '#attributes' => [
                'disabled' => 'disabled',
            ],
        ];

        $form['reason'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Reason'),
            '#required' => false,
            '#description' => $this->t('Please enter why you want to restore this snapshot.'),
            '#default_value' => $input['reason'] ?? null,
            '#rows' => 4,
        ];

        $form['append'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Keep existing content blocks'),
            '#required' => false,
            '#description' => $this->t('When checked this snapshot gets added to the existing content blocks. <br />If left unchecked the existing content blocks are replaced.'),
            '#default_value' => $input['append'] ?? false,
        ];

        $form['actions']['submit'] = $this->createSubmitButton('Restore snapshot');
        $form['actions']['cancel'] = $this->createCancelButton('Back');

        $form['title'] = [
            '#markup' => '<h2>Content of snapshot:</h2>',
        ];

        $form['table'] = [
            '#type' => 'table',
            '#header' => [
                'Type',
                'Label',
                'Message',
            ],
            '#rows' => [],
            '#empty' => $this->t('There are no content blocks in this snapshot.'),
        ];

        try {
            $blocks = $this->snapshotService->denormalize(
                $snapshot,
                $contentContainer,
                $host
            );
        } catch (\Exception $e) {
            if (!$this->isAjax()) {
                $this->messenger()->addError($e->getMessage());
            }
            $form['table']['#empty'] = $this->t('There is an error with this snapshot: <br />@error', ['@error' => $e->getMessage()]);
            $form['actions']['submit']['#attributes']['disabled'] = 'disabled';
            return $form;
        }

        foreach ($blocks as $i => $denormalizationResult) {
            $block = $denormalizationResult->getEntity();

            $errors = [];
            foreach ($denormalizationResult->getViolations() as $violation) {
                /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
                $errors[] = $violation->getMessage();
            }

            $form['table']['#rows'][$i] = [
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
                'errors' => [
                    'data' => [
                        '#markup' => implode('<br />', $errors),
                    ],
                ],
            ];

            if ($errors) {
                $form['table']['#rows'][$i]['#attributes']['class'][] = 'error';
            }
        }

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $input = $form_state->getUserInput();

        $blocks = $this->snapshotService->denormalize(
            $this->snapshot,
            $this->container,
            $this->host
        );

        /** @var \Drupal\Core\Entity\ContentEntityInterface[] $existing */
        $existing = $this->contentManager->getContent($this->host, $this->container->id());

        $tx = $this->db->startTransaction($this->host->uuid());
        $weight = 0;
        foreach ($existing as $block) {
            if (empty($input['append'])) {
                $block->delete();
                continue;
            }
            $_weight = (int) $block->get('wmcontent_weight')->value;
            if ($weight < $_weight) {
                $weight = $_weight;
            }
        }

        foreach ($blocks as $denormalizationResult) {
            $block = $denormalizationResult->getEntity();
            $block->set('wmcontent_weight', ++$weight);
            $block->save();
        }
        unset($tx);

        $log = SnapshotLog::create([
            'comment' => $input['reason'] ?? '',
            'user_id' => $this->currentUser()->id(),
            'snapshot' => $this->snapshot->id(),
            'source_entity_type' => $this->host->getEntityTypeId(),
            'source_entity_id' => $this->host->id(),
        ]);

        $log->save();

        if (!$this->isAjax()) {
            $this->messenger()->addStatus('Snapshot applied successfully.');
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

    /**
     * This is being called from the parent ::ajax handler
     * @see \Drupal\wmcontent\Form\Snapshot\SnapshotFormBase::ajax()
     */
    public function onAjax(array $form, FormStateInterface $formState)
    {
        $response = new AjaxResponse();

        // Redirect to the content blocks overview
        $response->addCommand(
            $this->messageCommand('Snapshot created. Reloading page.')
        );
        $response->addCommand(
            new RedirectCommand(
                // Redirect is set in ::submitForm
                $formState->getRedirect()->toString()
            )
        );

        return $response;
    }
}
