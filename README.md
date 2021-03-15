<p align="center">
  <img src="https://www.multisafepay.com/img/multisafepaylogo.svg" width="400px" position="center">
</p>

# MultiSafepay plugin for Magento 2 (GraphQL-only)

This is a module for MultiSafepay payments GraphQl support.
Before you get started, please read our [installation & configuration manual](https://docs.multisafepay.com/integrations/plugins/magento2/) first.

## About MultiSafepay ##
MultiSafepay is a collecting payment service provider which means we take care of the agreements, technical details and payment collection required for each payment method. You can start selling online today and manage all your transactions from one place.

## Supported Payment Methods ##
The supported Payment Methods & Giftcards for this plugin can be found over here: [Payment Methods & Giftcards](https://docs.multisafepay.com/plugins/magento2/faq/#available-payment-methods-in-magento-2)

## Requirements
- To use the plugin you need a MultiSafepay account. You can create a test account on https://testmerchant.multisafepay.com/signup
- Magento Open Source version 2.2.x & 2.3.x & 2.4.x
- PHP 7.1+

## Module suite

The new MultiSafepay Magento 2 plugin consists of several modules:

* [multisafepay-magento2-core](https://github.com/MultiSafepay/magento2-core) (Provides core functionalities)
* [multisafepay-magento2-frontend](https://github.com/MultiSafepay/magento2-frontend) (Enables use of the payment gateways in the Magento checkout)
* [multisafepay-magento2-adminhtml](https://github.com/MultiSafepay/magento2-adminhtml) (Makes it possible to enable/disable payment gateways and change the settings in the Magento backend)
* [multisafepay-magento2-msi](https://github.com/MultiSafepay/magento2-msi) (Handles stock when MSI is enabled)
* [multisafepay-magento2-catalog-inventory](https://github.com/MultiSafepay/magento2-catalog-inventory) (Handles stock when MSI is disabled)
* [multisafepay-magento2](https://github.com/MultiSafepay/magento2) (Meta package which installs all the above)

## Installation

This module can be installed via composer:

```shell
composer require multisafepay/magento2-graphql
```

Next, enable the module:
```bash
bin/magento module:enable MultiSafepay_ConnectCore MultiSafepay_ConnectGraphQl
```

Next, run the following commands:
```shell
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
```

**Please keep in mind that after installing this module, you will only have graphql compatability and core functionalities.**

For a quick installation of all the modules, we recommend using [the meta package](https://github.com/MultiSafepay/magento2) instead.

## Usage
To create an order using GraphQL, please take a look at the [Magento manual](https://devdocs.magento.com/guides/v2.4/graphql/tutorials/checkout/index.html)

### Example requests
- Get the issuers and additional data for selected MultiSafepay payment:
```graphql
query {
    cart(cart_id: "{ CART_ID }") {
        selected_payment_method {
            code
            multisafepay_additional_data {
                image
                is_preselected
            }
            multisafepay_available_issuers {
                code
                description
            }
        }
    }
}

```
- Get the available payments methods with issuers and additional data for MultiSafepay gateways:
```graphql
query {
    cart(cart_id: "{ CART_ID }") {
        available_payment_methods {
            code
            title
            multisafepay_available_issuers {
                code
                description
            }
            multisafepay_additional_data {
                image
                is_preselected
            }
        }
    }
}
```
- setPaymentMethodOnCart query example:
```graphql
mutation {
    setPaymentMethodOnCart(input: {
        cart_id: "{ CART_ID }"
        payment_method: {
            code: "multisafepay_ideal"
            multisafepay_ideal: {
                issuer_id: "3151"
            }
        }
    }) {
        cart {
            selected_payment_method {
                code
                multisafepay_additional_data {
                    image
                    is_preselected
                }
            }
        }
    }
}
```
- Place order request example. After placing the order, you will receive the `multisafepay_payment_url` on which the customer should be redirected to for placing a transaction:
```graphql
mutation {
    placeOrder(input: {cart_id: "{ CART_ID }"}) {
        order {
            order_number
            multisafepay_payment_url {
                payment_url
                error
            }
        }
    }
}
```
- Request example of a new mutation of `restoreQuote` for restoring the quote by Cart ID after, for example, an unsuccessfull payment:
```graphql
mutation {
    restoreQuote(input: {
        cart_id: "{ CART_ID }"
    })
}

```

## Support
You can create issues on our repository. If you need any additional help or support, please contact <a href="mailto:integration@multisafepay.com">integration@multisafepay.com</a>

We are also available on our Magento Slack channel [#multisafepay-payments](https://magentocommeng.slack.com/messages/multisafepay-payments/).
Feel free to start a conversation or provide suggestions as to how we can refine our Magento 2 plugin.

## A gift for your contribution
We look forward to receiving your input. Have you seen an opportunity to change things for better? We would like to invite you to create a pull request on GitHub.
Are you missing something and would like us to fix it? Suggest an improvement by sending us an [email](mailto:integration@multisafepay.com) or by creating an issue.

What will you get in return? A brand new designed MultiSafepay t-shirt which will make you part of the team!

## License
[Open Software License (OSL 3.0)](https://github.com/MultiSafepay/Magento2Msp/blob/master/LICENSE.md)

## Want to be part of the team?
Are you a developer interested in working at MultiSafepay? [View](https://www.multisafepay.com/careers/#jobopenings) our job openings and feel free to get in touch with us.

## Development (@dev)
@todo
