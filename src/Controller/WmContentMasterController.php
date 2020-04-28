<?php

namespace Drupal\wmcontent\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\wmcontent\Form\WmContentMasterForm;
use Drupal\wmcontent\WmContentContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WmContentMasterController implements ContainerInjectionInterface
{
    use StringTranslationTrait;

    /** @var FormBuilderInterface */
    protected $formBuilder;

    public static function create(ContainerInterface $container)
    {
        $instance = new static;
        $instance->formBuilder = $container->get('form_builder');

        return $instance;
    }

    public function overview(WmContentContainerInterface $container, EntityInterface $host)
    {
        return [
            'form' => $this->formBuilder->getForm(WmContentMasterForm::class, $host, $container),
            '#title' => $this->t(
                '%slug for %label',
                [
                    '%slug' => $container->getLabel(),
                    '%label' => $host->label(),
                ]
            ),
        ];
    }
}
