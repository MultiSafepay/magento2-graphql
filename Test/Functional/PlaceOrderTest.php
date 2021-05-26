<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * Copyright © 2021 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectGraphQl\Test\Functional;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQl\ResponseContainsErrorsException;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\BancontactConfigProvider;

class PlaceOrderTest extends GraphQlAbstract
{
    /**
     * @var GetMaskedQuoteIdByReservedOrderId
     */
    private $getMaskedQuoteIdByReservedOrderId;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->getMaskedQuoteIdByReservedOrderId = $objectManager->get(GetMaskedQuoteIdByReservedOrderId::class);
        $scopeConfig = $objectManager->get(ScopeConfigInterface::class);
        $scopeConfig->clean();
    }

    /**
     * @magentoConfigFixture default_store payment/multisafepay_bancontact/active 1
     *
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/set_guest_email.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_new_shipping_address.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_new_billing_address.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_flatrate_shipping_method.php
     *
     * @throws Exception
     */
    public function testPlaceOrderMutationWillReturnPaymentUrl(): void
    {
        $reservedOrderId = 'test_quote';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute($reservedOrderId);

        $this->setPaymentMethodOnCartMutation($maskedQuoteId);
        $response = $this->getMutation($maskedQuoteId);
        $order = $response['placeOrder']['order'];

        self::assertArrayHasKey('multisafepay_payment_url', $order);

        $multiSafepayPaymentUrl = $order['multisafepay_payment_url'];

        self::assertArrayHasKey('payment_url', $multiSafepayPaymentUrl);
        self::assertIsString($multiSafepayPaymentUrl['payment_url']);
        self::stringContains('https://testpayv2.multisafepay.com/connect/');
        self::assertEmpty($multiSafepayPaymentUrl['error']);
    }

    /**
     * @magentoConfigFixture default_store payment/multisafepay_bancontact/active 1
     *
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/set_guest_email.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_new_shipping_address.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_new_billing_address.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_flatrate_shipping_method.php
     *
     * @throws Exception
     */
    public function testPlaceOrderMutationWillReturnError(): void
    {
        $reservedOrderId = 'test_quote';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute($reservedOrderId);

        $this->expectException(ResponseContainsErrorsException::class);
        $this->expectExceptionMessage('Unable to place order: Enter a valid payment method and try again.');

        $this->getMutation($maskedQuoteId);
    }

    /**
     * @throws Exception
     */
    private function getMutation($maskedQuoteId)
    {
        return $this->graphQlMutation(
            <<<QUERY
mutation {
    placeOrder(input: {cart_id: "$maskedQuoteId"}) {
        order {
            order_number
            multisafepay_payment_url {
                payment_url
                error
            }
        }
    }
}
QUERY
        );
    }

    /**
     * @throws Exception
     */
    private function setPaymentMethodOnCartMutation($maskedQuoteId): void
    {
        $bancontactCode = BancontactConfigProvider::CODE;

        $this->graphQlMutation(
            <<<QUERY
mutation {
    setPaymentMethodOnCart(input: {
        cart_id: "$maskedQuoteId"
        payment_method: {
            code: "$bancontactCode"
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
QUERY
        );
    }
}
