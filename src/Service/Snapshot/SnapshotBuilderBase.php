<?php

namespace Drupal\wmcontent\Service\Snapshot;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

abstract class SnapshotBuilderBase
{
    public const VERSION = '2020/07/08 13:37';

    /** @var ConstraintViolationListInterface */
    protected $violations;

    abstract public function normalize(EntityInterface $block): array;

    abstract public function denormalize(array $data, string $sourceLangcode, string $targetLangcode): EntityInterface;

    public function version(): \DateTime
    {
        if (static::VERSION === self::VERSION) {
            throw new \RuntimeException(sprintf(
                'The VERSION of "%s" is still the default value. Please update it.',
                static::class
            ));
        }
        return new \DateTime(static::VERSION);
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        if (!isset($this->violations)) {
            $this->violations = new ConstraintViolationList([]);
        }

        return $this->violations;
    }

    public function getMetadata(EntityInterface $block): array
    {
        return [];
    }
}
