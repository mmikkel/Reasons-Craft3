# Reasons Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 2.3.0 - 2022-05-21

> {warning} Craft 4.0 has been released, finally adding field layout conditionals to core! As such Reasons has become redundant, and will not be upgraded to support Craft 4 or later releases. Before upgrading your site to Craft 4, make sure to note any conditionals that you'll want to re-create using the core feature, before uninstalling Reasons.      

### Fixed
- Fixes an issue where conditionals would not work inside element slideouts for new entries or categories created via their fieldtypes    

### Changed
- Reasons now requires Craft 3.7.0+  
- Reasons now requires PHP 7.2.5+  
- Updated plugin icon  

## 2.2.6 - 2022-01-13
### Fixed
- Fixes an issue where Reasons could create a significant amount of duplicate database queries inside the control panel. Fixes #15.

## 2.2.5 - 2021-06-15  
### Fixed  
- Fixes Craft 3.7 compatibility issues  

## 2.2.4 - 2021-01-20
### Fixed
- Fixes an issue where Lightswitch toggle fields would not work properly on Craft 3.5.18+  

## 2.2.3 - 2020-12-27  
### Fixed  
- Fixes Postgres SQL errors  

## 2.2.2 - 2020-10-08
### Fixed  
- Fixes an issue where Reasons could cause an exception when installing Craft using existing project config Yaml files  
- Fixes an issue where Reasons would not delete conditionals from the Project Config when related field layouts were deleted in Craft  
- Fixes an issue where deleted conditionals would not be removed from the Project Config when it was rebuilt  

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
