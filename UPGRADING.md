# Upgrade Guide

This document describes breaking changes and how to upgrade. For a
complete list of changes including minor and patch releases, please
refer to the [`CHANGELOG`](CHANGELOG.md).

## `2.2.1` and `2.0.2`

The arguments of [SnapshotBuilderBase::denormalize()](https://github.com/wieni/wmcontent/blob/2.2.1/src/Service/Snapshot/SnapshotBuilderBase.php#L18) have changed.
You'll need to update every class that extends `SnapshotBuilderBase`.

```diff
- public function denormalize(array $data, string $langcode): EntityInterface;
+ public function denormalize(array $data, string $sourceLangcode, string $targetLangcode): EntityInterface;
```

## v1
### Type hints
Argument and return types were added for most methods and functions, so
any method or function extending ones provided by this module should be
checked.

### `WmContentManagerInterface`
An interface was added for the existing `WmContentManager` service, so
type hints should be changed to the interface.

The `isContentBlock` method was renamed to `isChild` 

The `emitChangedEvent` method was removed.

The following method: 
```
public function getContainers(ContentEntityInterface $contentBlock = null)
``` 
was replaced by the following methods: 
```
public function getContainers(): array
public function getChildContainers(EntityInterface $child): array
```

### `WmContentContainerInterface`
Type hints and two new methods were added to all methods to this
interface.
- `public function getShowAlignmentColumn(): bool`
- `public function hasChild(EntityInterface $child): bool`

### Removed services
The `wmcontent.descriptive_titles` and
`wmcontent.entity_type.bundle.info` services were removed, so all
references should be removed as well.

### Routes
- the `container` parameter on the following routes is now automatically
  converted to the respective entity:
  - `entity.{type}.wmcontent_overview`
  - `entity.{type}.wmcontent_add`
  - `entity.{type}.wmcontent_edit`
  - `entity.{type}.wmcontent_delete`

- the `child_id` parameter on the following routes is now renamed to
  `child` and automatically converted to the respective entity:
  - `entity.{type}.wmcontent_edit`
  - `entity.{type}.wmcontent_delete`

### Theming
The `wmcontent_section`, `paragraph`, `items` and `paragraph_cards`
theme implementations, together with their underlying logic, were
removed. A less complex alternative using just the `wmcontent` theme
implementation is provided.
