# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
