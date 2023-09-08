<?php

namespace Drupal\wmcontent\ArgumentResolver;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\wmcontent\WmContentContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class HostEntityResolver implements ValueResolverInterface
{
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return $argument->getName() === 'host'
            && $request->attributes->get('container') instanceof WmContentContainerInterface
            && is_a($argument->getType(), ContentEntityInterface::class, true);
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($this->supports($request, $argument) === false) {
            return [];
        }

        $container = $request->attributes->get('container');
        yield $request->attributes->get($container->getHostEntityType());
    }
}
