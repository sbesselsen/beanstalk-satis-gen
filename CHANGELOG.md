# Changelog

## 0.2.1

### Fixed
- Fixed a bug where parsed_to could be set to `null` in some cases

## 0.2.0

### Added
- Symfony console integration.
- Logging using PSR-3.
- `generate` command that only parses from a certain git changeset.
- A parsed_to settings to only parse from that changeset.
- `wordpress-muplugin` and `wordpress-theme` to the allowable package types.

### Deprecated
- Calling the script without a command, the old functionality is present using `update-satis.phar generate`

### Fixed
- Only add packages if they have a type, packages without a type are now ignored.

## 0.1.7

### Added
- `drupal-module` to the list of allowable types

## 0.1.6

### Added
- Check on type of composer packages

## 0.1.5

### Fixed
- Naming collision with other packages

## 0.1.4

### Fixed
- Autoloading

## 0.1.3

## Added
- PHP Version requirements to composer.json

## 0.1.2

### Fixed
- Missing bin file

## 0.1.1
- Initial release