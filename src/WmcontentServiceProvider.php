<?php

namespace Drupal\wmcontent;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Symfony\Component\DependencyInjection\Reference;

class WmcontentServiceProvider implements ServiceModifierInterface
{
    public function alter(ContainerBuilder $container)
    {
        $argumentResolver = $container->getDefinition('http_kernel.controller.argument_resolver');
        $argumentValueResolvers = $argumentResolver->getArgument(1);
        array_unshift($argumentValueResolvers, new Reference('wmcontent.argument_resolver.host_entity'));
        $argumentResolver->setArgument(1, $argumentValueResolvers);
    }
}
