# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.2.0] - 2025-07-09
### Added
- PLGMAG2V2-857: Added debug mode in the payment component data request

## [4.1.0] - 2025-05-01
### Added
- PLGMAG2V2-814: Add instruction field for payment methods

### Fixed
- PLGMAG2V2-842: Fix PHP 8.4 deprecations

### Removed
- DAVAMS-840: Remove gender requirement for in3

## [4.0.1] - 2024-10-30
### Fixed
- PLGMAG2V2-800: Fixed an error when trying to place a redirect order using a payment method which also supports Payment Components (thanks to @indykoning)

## [4.0.0] - 2024-05-16
### Removed
- PLGMAG2V2-786: Removed iDEAL issuers from GraphQL
- Removed the dependency on the MultiSafepay_ConnectFrontend module

## [3.4.0] - 2024-05-16
### Added
- PLGMAG2V2-662: Add support for [Payment components](https://docs.multisafepay.com/docs/payment-components)

### Changed
- DAVAMS-700: Refactor in3

## [3.3.0] - 2023-04-13
### Added
- Add compatibility with ^3.0 version of core module and ^2.0 of frontend module

## [3.2.0] - 2023-03-27
### Added
- Add to the plugin info object, within the order request, information about the integration

### Changed
- Fixed an issue where in rare cases, retrieve information from the checkout session could be unreliable in a GraphQL request

## [3.1.0] - 2022-10-04
### Changed
- Made the fields for E-invoicing optional.

## [3.0.0] - 2022-08-23
### Added
- Added the MyBank payment method

### Changed
- Changed the input for iDEAL issuers to be identical to the MyBank issuers

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
