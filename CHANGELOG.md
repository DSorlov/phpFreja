# phpFreja

Changelog for phpFreja.

The format is based on [Keep a Changelog][keep-a-changelog]
<!-- and this project adheres to [Semantic Versioning][semantic-versioning]. -->

## [Unreleased]
- Nothing right now

## [1.1] (2023-03-22)
- Impelented new keys for jwt 2023
- Fixed [#2](https://github.com/DSorlov/phpFreja/issues/2)

## [1.0] (2020-05-02)

### BREAKING CHANGES
- Renamed the main class from 'frejaeID' to new name 'phpFreja'
- Renamed the php file from 'frejaeID.php' to 'freja.php'
- Output from the checkSignatureRequest have changed

### Changed
- Included a fork from [php-jws](https://github.com/Gamegos/php-jws) in repo

### Added
- Added JWS validation as suggested by [#1](https://github.com/DSorlov/phpFrejaeid/issues/1)
- PEM-files added for JWS validation

## [0.1] (2019-10-14)

### Changed
- Initial releases

[keep-a-changelog]: http://keepachangelog.com/en/1.0.0/
[1.1]: https://github.com/DSorlov/phpFreja/releases/tag/v1.1
[1.0]: https://github.com/DSorlov/phpFreja/releases/tag/v1.0
[0.1]: https://github.com/DSorlov/phpFreja