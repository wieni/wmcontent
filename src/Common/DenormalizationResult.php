<?php

namespace Drupal\wmcontent\Common;

use Drupal\Core\Entity\EntityInterface;
use Drupal\wmcontent\Service\Snapshot\SnapshotBuilderBase;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class DenormalizationResult
{
    /** @var \Drupal\Core\Entity\EntityInterface */
    protected $entity;
    /** @var \Drupal\wmcontent\Service\Snapshot\SnapshotBuilderBase */
    protected $builder;

    public function __construct(EntityInterface $entity, SnapshotBuilderBase $builder)
    {
        $this->entity = $entity;
        $this->builder = $builder;
    }

    public function getEntity(): EntityInterface
    {
        return $this->entity;
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->builder->getViolations();
    }
}
