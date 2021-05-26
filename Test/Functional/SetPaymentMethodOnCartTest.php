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
use MultiSafepay\ConnectCore\Model\Ui\Gateway\AfterpayConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\BancontactConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\IdealConfigProvider;

class SetPaymentMethodOnCartTest extends GraphQlAbstract
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
    public function testSetPaymentMethodOnCartMutation(): void
    {
        $reservedOrderId = 'test_quote';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute($reservedOrderId);

        $paymentMethods = $this->getAvailablePaymentMethodsQuery($maskedQuoteId);
        foreach ($paymentMethods['cart']['available_payment_methods'] as $paymentMethod) {
            if ($paymentMethod['code'] === BancontactConfigProvider::CODE) {
                $response = $this->getMutation($maskedQuoteId);

                $selectedPaymentMethod = $response['setPaymentMethodOnCart']['cart']['selected_payment_method'];

                self::assertArrayHasKey('code', $selectedPaymentMethod);
                self::assertSame(BancontactConfigProvider::CODE, $selectedPaymentMethod['code']);
                break;
            }
        }
    }

    /**
     * @magentoConfigFixture default_store payment/multisafepay_ideal/active 1
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
    public function testSetPaymentMethodOnCartMutationWithIssuer(): void
    {
        $reservedOrderId = 'test_quote';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute($reservedOrderId);

        $paymentMethods = $this->getAvailablePaymentMethodsQuery($maskedQuoteId);
        foreach ($paymentMethods['cart']['available_payment_methods'] as $paymentMethod) {
            if ($paymentMethod['code'] === IdealConfigProvider::CODE) {
                $issuerCode = reset($paymentMethod['multisafepay_available_issuers'])['code'];

                $this->expectException(ResponseContainsErrorsException::class);
                $this->expectExceptionMessage('Please check and set the correct Issuer ID.');
                $this->getMutationForIdeal($maskedQuoteId, 'false-issuer-code');

                $response = $this->getMutationForIdeal($maskedQuoteId, $issuerCode);

                $selectedPaymentMethod = $response['setPaymentMethodOnCart']['cart']['selected_payment_method'];

                self::assertArrayHasKey('code', $selectedPaymentMethod);
                self::assertSame(IdealConfigProvider::CODE, $selectedPaymentMethod['code']);
                break;
            }
        }
    }

    /**
     * @magentoConfigFixture default_store payment/multisafepay_afterpay/active 1
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
    public function testSetPaymentMethodOnCartMutationWithAdditionalInput(): void
    {
        $reservedOrderId = 'test_quote';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute($reservedOrderId);

        $paymentMethods = $this->getAvailablePaymentMethodsQuery($maskedQuoteId);
        foreach ($paymentMethods['cart']['available_payment_methods'] as $paymentMethod) {
            if ($paymentMethod['code'] === AfterpayConfigProvider::CODE) {

                $response = $this->getMutationForAfterpay($maskedQuoteId);
                $selectedPaymentMethod = $response['setPaymentMethodOnCart']['cart']['selected_payment_method'];

                self::assertArrayHasKey('code', $selectedPaymentMethod);
                self::assertSame(AfterpayConfigProvider::CODE, $selectedPaymentMethod['code']);
                break;
            }
        }
    }

    /**
     * @throws Exception
     */
    private function getMutation($maskedQuoteId)
    {
        $bancontactCode = BancontactConfigProvider::CODE;

        return $this->graphQlMutation(
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

    /**
     * @throws Exception
     */
    private function getMutationForIdeal($maskedQuoteId, $issuerCode = null)
    {
        $idealCode = IdealConfigProvider::CODE;

        return $this->graphQlMutation(
            <<<QUERY
mutation {
    setPaymentMethodOnCart(input: {
        cart_id: "$maskedQuoteId"
        payment_method: {
            code: "$idealCode"
            $idealCode: {
                issuer_id: "$issuerCode"
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
QUERY
        );
    }

    /**
     * @throws Exception
     */
    private function getMutationForAfterpay(string $maskedQuoteId)
    {
        $afterpayCode = AfterpayConfigProvider::CODE;

        return $this->graphQlMutation(
            <<<QUERY
mutation {
    setPaymentMethodOnCart(input: {
        cart_id: "$maskedQuoteId"
        payment_method: {
            code: "$afterpayCode"
            $afterpayCode: {
                date_of_birth: "10-10-2000",
                gender: "mr"
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
QUERY
        );
    }

    /**
     * @throws Exception
     */
    private function getAvailablePaymentMethodsQuery($maskedQuoteId)
    {
        return $this->graphQlQuery(
            <<<QUERY
query {
    cart(cart_id: "$maskedQuoteId") {
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
QUERY
        );
    }
}
