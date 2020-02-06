# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.2] - 2020-02-06
### Fixed
- Remove PHP 7.3 specific syntax

## [1.0.1] - 2020-02-06
### Fixed
- Remove PHP 7.3 specific syntax

## [1.0.0] - 2020-01-13
More information about breaking changes, removed functionality and their
alternatives is provided in the [Upgrade Guide](UPGRADING.md).

### Added
- Add coding standard fixers
- Add issue & pull request templates

### Changed
- Refactor code with code style, typehinting & readability in mind
- Change PHP version requirement to 7.1
- Change the container form to show all content entity types with
  canonical link templates in the dropdowns
- Change the container form to only show bundles when an entity type is 
  selected
- Change routes to convert the `container` and `child` parameters to
  their respective entities
- Change link templates to only add to content entity types
- Update .gitignore
- Update README
- Update module title & description
- Normalize composer.json
- Re-indent & reformat YAML files

### Fixed
- Add default value for child_bundles_default
- Remove support for custom paragraph entity type & section rendering

### Removed
- Remove config subscriber
- Remove `wmcontent.descriptive_titles` service in favour of controller methods
- Remove unused `wmcontent.entity_type.bundle.info` service
  controller methods
- Remove eck, node & wmmodel dependencies

## [0.6.1] - 2019-12-13
### Added
- Add CHANGELOG.md

### Fixed
- Add missing IndexableBaseFieldDefinition class

## [0.6.0] - 2019-12-12
### Added
- Add indexes to the following base fields
  ([#32](https://github.com/wieni/wmcontent/issues/32)):
  - wmcontent_weight
  - wmcontent_parent
  - wmcontent_parent_type
  - wmcontent_container
- Add php & drupal/core composer dependencies

### Changed
- Sort content blocks in master form alphabetically
- Replace deprecated automatic entity updates with own solution
- Replace usages of third-party deprecated code
- Normalize composer.json

## [0.5.2] - 2019-06-06
### Changed
- Improve error handling in WmContentManager::getHost

### Removed
- Remove unused config

## [0.5.1] - 2019-06-03
- Improve error handling in the master form controller

## [0.5.0] - 2019-04-02
### Changed
- Update host changed time on content block save

## [0.4.25] - 2019-04-02
### Fixed
- Add missing use statement

## [0.4.24] - 2019-03-28
### Fixed
- Fix when loading translation in WmContentManager::getHost

## [0.4.23] - 2019-03-28
### Fixed
- Fix language related bug in WmContentManager::getHost

## [0.4.22] - 2019-03-20
### Changed
- Choose the first option if no size / alignment defaults are provided

### Fixed
- Fix hiding single option sizes / alignments

## [0.4.21] - 2019-03-15
### Fixed
- Fix notice

## [0.4.20] - 2019-02-18
### Changed
- Changes sizes, alignments & defaults to be truly dynamic
