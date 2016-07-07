# WebHomeBank Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).


## [1.1.0] - 2016-07-07
### Added
- New "Settings" menu that allows selecting language/currency/theme for the current session
- New "loading" page when importing XHB to database (can take some time)
- Initialization and import of XHB has been totally rewritten in a more clean way*
- Sessions on files can now be stored in a dedicated folder ([PR#140](https://github.com/bcosca/fatfree-core/issues/140) on bcosca/fatfree-core)
- Operations in the future are now in italic (as in HB)
- Default periods (operations, vehicles) are now configurable via local.ini
- Unit test class for `\Xhb\Model\Xhb\DateHelper`
- Dockerfile now uses [php:7-apache](https://hub.docker.com/_/php/) (faster)
- Grand total in balance report chart on accounts page
- Period "last 120 days" has been replaced by "last 12 months"
- Nav bar is now sticky with modern theme
- Charts now have a cool "loading" animation (pie)
- Moved Zend\Db\Sql\InsertMultiple to a dedicated public repository
- Updated Chartjs (1.1.0)

### Fixed
- Locale, currency and theme are now stored in controllers cache key
- Plenty of issues with periods and dates
- Vehicle cost charts default date period
- Modern theme's navigation menu on mobile devices and more generally responsiveness of UI (still not perfect though)
- Dimension/aspect ratio on Doughtnut charts
- FR translation
- Lots of other stuffs

### Removed
- References to `\app\models\core\Log` from `app/lib/`
- XHB lib no longer uses ResourceManager class

## [1.0.0] - 2015-11-14
- Initial release
