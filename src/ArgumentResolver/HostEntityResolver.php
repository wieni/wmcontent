<?php

namespace Drupal\wmcontent\ArgumentResolver;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\wmcontent\WmContentContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class HostEntityResolver implements ArgumentValueResolverInterface
{
    public function supports(Request $request, ArgumentMetadata $argument)
    {
        return $argument->getName() === 'host'
            && $request->attributes->get('container') instanceof WmContentContainerInterface
            && is_a($argument->getType(), ContentEntityInterface::class, true);
    }

    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        $container = $request->attributes->get('container');
        yield $request->attributes->get($container->getHostEntityType());
    }
}
