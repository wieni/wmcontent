<?php

namespace Drupal\wmcontent\Service\Snapshot;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Drupal\wmcontent\Common\DenormalizationResult;
use Drupal\wmcontent\Entity\Snapshot;
use Drupal\wmcontent\Form\Snapshot\SnapshotFormBase;
use Drupal\wmcontent\WmContentContainerInterface;

class SnapshotService
{
    use StringTranslationTrait;

    /** @var \Drupal\Core\Language\LanguageManagerInterface */
    protected $languageManager;
    /** @var \Drupal\wmcontent\Service\Snapshot\SnapshotBuilderFactory */
    protected $snapshotBuilderFactory;
    /** @var string */
    protected $environment;
    /** @var string */
    protected $secret;

    public function __construct(
        LanguageManagerInterface $languageManager,
        SnapshotBuilderFactory $snapshotBuilderFactory,
        string $environment,
        string $secret
    ) {
        $this->languageManager = $languageManager;
        $this->snapshotBuilderFactory = $snapshotBuilderFactory;
        $this->environment = $environment;
        $this->secret = $secret;
    }

    public function isSnapshotable(EntityInterface $entity): bool
    {
        $builder = $this->snapshotBuilderFactory->getSnapshotBuilder(
            $entity->getEntityTypeId(),
            $entity->bundle()
        );

        return $builder !== null;
    }

    /**
     * @param \Drupal\Core\Entity\EntityInterface[] $blocks
     * @return Snapshot
     */
    public function createSnapshot(
        array $blocks,
        string $title,
        string $description,
        UserInterface $user,
        WmContentContainerInterface $container,
        ?EntityInterface $host,
        ?string $environment
    ): Snapshot
    {
        $normalized = [];

        foreach ($blocks as $block) {
            $builder = $this->getSnapshotBuilder($block);

            $record = [
                'metadata' => [
                    'builder_version' => $builder->version()->getTimestamp(),
                    'builder_version_human' => $builder->version()->format('d/m/Y H:i'),
                    'entityTypeId' => $block->getEntityTypeId(),
                    'bundle' => $block->bundle(),
                    'entityId' => $block->id(),
                    'uuid' => $block->uuid(),
                ] + $builder->getMetadata($block),
                'data' => $builder->normalize($block),
            ];
            unset($record['data']['id']);
            unset($record['data']['uuid']);
            unset($record['data']['wmcontent_parent']);
            unset($record['data']['wmcontent_parent_type']);
            unset($record['data']['wmcontent_container']);

            $normalized[] = $record;
        }

        /** @var Snapshot $snapshot */
        $snapshot = Snapshot::fromArray([
            'title' => $title,
            'comment' => $description,
            'user_id' => $user,
            'source_entity_type' => $host ? $host->getEntityTypeId() : null,
            'source_entity_id' => $host ? $host->id() : null,
            'wmcontent_container' => $container,
            'environment' => $environment ?: $this->environment,
            'blob' => $normalized,
        ], $this->languageManager->getCurrentLanguage()->getId());

        return $snapshot;
    }

    /**
     * Convert array of normalized content blocks back to entities
     *
     * @param array $data
     * @return DenormalizationResult[]
     */
    public function denormalize(
        Snapshot $snapshot,
        WmContentContainerInterface $container,
        EntityInterface $host
    ): array {
        $denormalized = [];

        foreach ($snapshot->getBlob() as $block) {
            if (empty($block['metadata']['entityTypeId']) || empty($block['metadata']['bundle'])) {
                throw new \RuntimeException(sprintf(
                    'Invalid data array. Cannot denormalize because missing "entityTypeId" and/or "bundle" key'
                ));
            }
            $builder = $this->getSnapshotBuilder($block['metadata']['entityTypeId'], $block['metadata']['bundle']);

            $block['data']['wmcontent_container'] = [
                [
                    'value' => $container->id(),
                ]
            ];
            $block['data']['wmcontent_parent'] = [
                [
                    'value' => $host->id(),
                ]
            ];
            $block['data']['wmcontent_parent_type'] = [
                [
                    'value' => $host->getEntityTypeId(),
                ]
            ];

            $denormalized[] = new DenormalizationResult(
                $builder->denormalize($block['data'], $this->languageManager->getCurrentLanguage()->getId()),
                $builder
            );
        }

        return $denormalized;
    }

    public function getEntityOperations(Snapshot $snapshot, RouteMatchInterface $currentRouteMatch): array
    {
        $operations = [];
        $container = $snapshot->getContainer();
        if (!$container instanceof WmContentContainerInterface) {
            return [];
        }
        $host = $currentRouteMatch->getParameter($container->getHostEntityType());
        if (!$host instanceof EntityInterface) {
            return [];
        }

        $snapshotBaseRoute = sprintf('entity.%s.wmcontent_snapshot', $host->getEntityTypeId());

        $operations['restore'] = [
            'title' => $this->t('Restore'),
            'url' => Url::fromRoute($snapshotBaseRoute . '.restore', [
                $host->getEntityTypeId() => $host->id(),
                'container' => $container->id(),
                'snapshot' => $snapshot->id(),
            ]),
            'attributes' => [
                'class' => ['use-ajax'],
                'data-dialog-type' => 'modal',
                'data-dialog-options' => json_encode(
                    SnapshotFormBase::MODAL_DIALOG_OPTIONS,
                    JSON_THROW_ON_ERROR
                ),
            ],
        ];

        $operations['export'] = [
            'title' => $this->t('Export code'),
            'url' => Url::fromRoute($snapshotBaseRoute . '.export', [
                $host->getEntityTypeId() => $host->id(),
                'container' => $container->id(),
                'snapshot' => $snapshot->id(),
            ]),
            'attributes' => [
                'class' => ['use-ajax'],
                'data-dialog-type' => 'modal',
                'data-dialog-options' => json_encode(
                    SnapshotFormBase::MODAL_DIALOG_OPTIONS,
                    JSON_THROW_ON_ERROR
                ),
            ],
        ];

        return $operations;
    }

    /**
     * @param string|EntityInterface $entityTypeId
     * @param string|null $bundle
     * @return \Drupal\wmcontent\Service\Snapshot\SnapshotBuilderBase
     */
    protected function getSnapshotBuilder($entityTypeId, ?string $bundle = null): SnapshotBuilderBase
    {
        if ($entityTypeId instanceof EntityInterface) {
            $bundle = $entityTypeId->bundle();
            $entityTypeId = $entityTypeId->getEntityTypeId();
        }

        $builder = $this->snapshotBuilderFactory->getSnapshotBuilder(
            $entityTypeId,
            $bundle
        );

        if (!$builder) {
            throw new \RuntimeException(sprintf(
                'No snapshot builder found for %s.%s',
                $entityTypeId,
                $bundle
            ));
        }

        return $builder;
    }

    public function export(Snapshot $snapshot): string
    {
        $blob = $snapshot->toArray();
        $blob['hmac'] = $this->hmac($blob);
        return base64_encode(json_encode($blob, JSON_THROW_ON_ERROR));
    }

    public function import(string $data): Snapshot
    {
        $blob = json_decode(base64_decode($data), true, 512, JSON_THROW_ON_ERROR);
        $hmac = $blob['hmac'] ?? '';
        unset($blob['hmac']);

        if (!hash_equals($hmac, $this->hmac($blob))) {
            throw new \Exception('Snapshot is invalid.');
        }

        return Snapshot::fromArray($blob, $this->languageManager->getCurrentLanguage()->getId());
    }

    protected function hmac(array $blob): string
    {
        return hash_hmac(
            'sha256',
            json_encode($blob, JSON_THROW_ON_ERROR),
            $this->secret
        );
    }
}
