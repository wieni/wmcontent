<?php

namespace Drupal\wmcontent\EventSubscriber;

use Drupal\core_event_dispatcher\Event\Theme\TemplatePreprocessDefaultVariablesAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class DefaultVariableSubscriber implements EventSubscriberInterface
{
    /** @var RequestStack */
    protected $requestStack;

    public function __construct(
        RequestStack $requestStack,
    ) {
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'hook_event_dispatcher.theme.template_preprocess_default_variables_alter' => 'alter',
        ];
    }

    public function alter(TemplatePreprocessDefaultVariablesAlterEvent $event)
    {
        $variables = &$event->getVariables();
        $request = $this->requestStack->getCurrentRequest();

        $variables['is_preview'] = ($request?->query->get('preview', 'false') === 'true');
    }
}
