# Reasons Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 2.2.1 - 2020-08-13

### Fixed
- Fixes an issue introduced in Craft 3.5.3, where conditionals would not render if field handles were visible  

## 2.2.0 - 2020-07-16

### Added
- Adds Craft 3.5 compatibility. Thanks @brandonkelly!
  
### Changed
- Reasons now requires Craft 3.5.0-RC1 or later

### Fixed
- Fixes an issue where conditionals could disappear from the Project Config when it was rebuilt

## 2.1.2 - 2020-06-12

### Added
- Adds support for Tags

### Fixed
- Fixes an issue where Reasons could break element editor modals for Tags

## 2.1.1 - 2020-06-08

### Fixed
- Fixes an issue where Reasons could throw an exception when syncing project config via console
- Fixes an issue where conditionals could be truncated on field layout save
- Fixes an issue where multiple toggle fields per target field wouldn't work as expected

## 2.1.0 - 2020-06-01

### Added
- Adds support for Project Config

## 2.0.1 - 2020-06-01

### Fixed
- Fixes an issue where it could be possible save multiple conditional records per field layout
- Fixes an issue where it wasn't possible to clear conditionals

## 2.0.0 - 2020-06-01

### Added
- Initial public release
