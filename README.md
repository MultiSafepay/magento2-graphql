<p align="center">
  <img src="https://camo.githubusercontent.com/517483ae0eaba9884f397e9af1c4adc7bbc231575ac66cc54292e00400edcd10/68747470733a2f2f7777772e6d756c7469736166657061792e636f6d2f66696c6561646d696e2f74656d706c6174652f696d672f6d756c7469736166657061792d6c6f676f2d69636f6e2e737667" width="400px" position="center">
</p>

# MultiSafepay plugin for Magento 2 (GraphQL module)

This module provides GraphQL support for MultiSafepay payments.
For a complete installation of all our features, please check out our [meta package](https://github.com/MultiSafepay/magento2/).

## Installation

This module can be installed via composer:

```shell
composer require multisafepay/magento2-graphql
```

Next, enable the module:
```bash
bin/magento module:enable MultiSafepay_ConnectCore MultiSafepay_ConnectFrontend MultiSafepay_ConnectAdminhtml MultiSafepay_ConnectGraphQl
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
- setPaymentMethodOnCart query with issuers example:
```graphql
mutation {
    setPaymentMethodOnCart(input: {
        cart_id: "{ CART_ID }"
        payment_method: {
            code: "multisafepay_mybank"
            multisafepay_mybank: {
                issuer_id: "CT000003-it-1"
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
- To retrieve MultiSafepay request data which includes information about Payment Component, Apple Pay or/and Google Pay, you can use the following query:
```graphql
query MultisafepayPaymentRequestData {
    multisafepayPaymentRequestData(cart_id: { CART_ID }) {
        apiToken
        apiTokenLifeTime
        cartTotal
        currency
        environment
        locale
        paymentComponentContainerId
        payment_component_template_id
        storeId
        applePayButton {
            applePayButtonId
            getMerchantSessionUrl
            isActive
            additionalTotalItems {
                amount
                label
            }
            cartItems {
                label
                price
            }
        }
        googlePayButton {
            accountId
            googlePayButtonId
            isActive
            mode
            merchantInfo {
                merchantId
                merchantName
            }
        }
        paymentComponentConfig {
            gatewayCode
            paymentMethod
            paymentType
            additionalInfo {
                image
                is_preselected
                vaultCode
            }
            tokens {
                bin
                code
                display
                expired
                expiry_date
                last4
                model
                name_holder
                token
            }
        }
    }
}
```

- setPaymentMethodOnCart query with payment component payload example: (for more information on how to retrieve the payload, see the [MultiSafepay documentation](https://docs.multisafepay.com/docs/payment-component-single/))
```graphql
mutation {
    setPaymentMethodOnCart(input: {
        cart_id: "{ CART_ID }"
        payment_method: {
            code: "multisafepay_creditcard"
            multisafepay_creditcard: {
                payload: "xxxx"
                tokenize: true
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
        } 
    )

```
- Get MultiSafepay payment url from a placed order: 
```graphql
query {
    customer {
        orders(
            pageSize: 10
        ) {
            total_count
            items {
                id
                increment_id
                multisafepay_payment_url
            }
        }
    }
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
