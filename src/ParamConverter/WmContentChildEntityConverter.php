<?php

namespace Drupal\wmcontent\ParamConverter;

use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\wmcontent\WmContentContainerInterface;
use Symfony\Component\Routing\Route;

class WmContentChildEntityConverter extends EntityConverter
{
    public function applies($definition, $name, Route $route)
    {
        if (empty($definition['type'])) {
            return false;
        }

        $parts = explode(':', $definition['type'], 2);

        if (count($parts) !== 2) {
            return false;
        }

        [$type, $entityTypeId] = $parts;

        if ($type !== 'wmcontent-child') {
            return false;
        }

        return $this->entityTypeManager->hasDefinition($entityTypeId);
    }

    protected function getEntityTypeFromDefaults($definition, $name, array $defaults)
    {
        $container = $defaults['container'];

        if (!$container instanceof WmContentContainerInterface) {
            $container = $this->entityTypeManager
                ->getStorage('wmcontent_container')
                ->load($container);
        }

        return $container->getChildEntityType();
    }
}
