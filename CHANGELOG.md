# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
## [2.0.0] - 2022-05-04
### Added
- Added required parameter afterpay_terms for the AfterPay gateway

## [1.2.0] - 2021-11-30
### Added
- Added a query for getting the MultiSafepay payment link

### Changed
- Changed the way the payment link is retrieved from the order

## [1.1.1] - 2021-07-20
### Added
- Added functional test coverage for all the queries and mutations

### Fixed
- Fixed phone number being required for AfterPay and in3

## [1.1.0] - 2021-03-26
### Added
- Added support for PWA Studio

### Removed
- Removed obsolete emandate field from Direct Debit checkout

## [1.0.1] - 2021-03-19
### Added
- Added compatibility for MultiSafepay and ScandiPWA frontend integration [plugin](https://github.com/MultiSafepay/scandipwa-multisafepay-payment-integration)
- Added dependency in composer.json from basic MultiSafepay plugins: Core, Frontend, Adminhtml

## [1.0.0] - 2021-02-16
### Added
- First public release
