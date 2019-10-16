<?php

/**
 * @file
 * Contains \Drupal\wmcontent\Form\WmContentContainerDeleteForm.
 */

namespace Drupal\wmcontent\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a deletion confirmation form for wmcontent container entity.
 */
class WmContentContainerDeleteForm extends EntityConfirmFormBase
{
    /** @var MessengerInterface */
    protected $messenger;

    public static function create(ContainerInterface $container)
    {
        $instance = parent::create($container);
        $instance->messenger = $container->get('messenger');

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuestion()
    {
        return $this->t('Are you sure you want to delete the container %name?', array('%name' => $this->entity->label()));
    }

    /**
     * {@inheritdoc}
     */
    public function getCancelUrl()
    {
        return new Url('wmcontent.collection');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfirmText()
    {
        return $this->t('Delete');
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this->entity->delete();
        $this->logger('wmcontent_container_entity')->notice('Container %name has been deleted.', array('%name' => $this->entity->label()));
        $this->messenger->addStatus($this->t('Container %name has been deleted.', array('%name' => $this->entity->label())));
        $form_state->setRedirectUrl($this->getCancelUrl());
    }

}
