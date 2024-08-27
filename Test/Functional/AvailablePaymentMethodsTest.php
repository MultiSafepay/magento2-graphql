<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * See DISCLAIMER.md for disclaimer details.
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectGraphQl\Test\Functional;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId;
use Magento\Store\Model\App\Emulation;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\MyBankConfigProvider;

class AvailablePaymentMethodsTest extends GraphQlAbstract
{

    /**
     * @var GetMaskedQuoteIdByReservedOrderId
     */
    private $getMaskedQuoteIdByReservedOrderId;

    /**
     * @var Emulation
     */
    private $emulation;

    /**
     * @var MyBankConfigProvider
     */
    private $myBankConfigProvider;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->getMaskedQuoteIdByReservedOrderId = $objectManager->get(GetMaskedQuoteIdByReservedOrderId::class);
        $this->emulation = $objectManager->get(Emulation::class);
        $this->myBankConfigProvider = $objectManager->get(MyBankConfigProvider::class);
        $scopeConfig = $objectManager->get(ScopeConfigInterface::class);
        $scopeConfig->clean();
    }

    /**
     * @magentoConfigFixture default_store multisafepay/general/preselected_method multisafepay_mybank
     * @magentoConfigFixture default_store payment/multisafepay_mybank/active 1
     * @magentoConfigFixture default_store payment/multisafepay_afterpay/active 1
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
    public function testAvailablePaymentMethodsQueryWithIssuersAndAdditionalData(): void
    {
        $reservedOrderId = 'test_quote';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute($reservedOrderId);

        $response = $this->getQuery($maskedQuoteId);

        self::assertArrayHasKey('cart', $response);
        self::assertNotEmpty($response['cart']);

        self::assertArrayHasKey('available_payment_methods', $response['cart']);
        self::assertNotEmpty($response['cart']['available_payment_methods']);

        $this->emulation->startEnvironmentEmulation(1, Area::AREA_FRONTEND, true);
        $imagePath = $this->myBankConfigProvider->getImage();
        $this->emulation->stopEnvironmentEmulation();

        foreach ($response['cart']['available_payment_methods'] as $paymentMethod) {
            self::assertArrayHasKey('code', $paymentMethod);
            self::assertArrayHasKey('title', $paymentMethod);
            self::assertArrayHasKey('multisafepay_available_issuers', $paymentMethod);
            self::assertArrayHasKey('multisafepay_additional_data', $paymentMethod);

            $additionalData = $paymentMethod['multisafepay_additional_data'];

            self::assertArrayHasKey('image', $additionalData);
            self::assertArrayHasKey('is_preselected', $additionalData);

            if ($paymentMethod['code'] === MyBankConfigProvider::CODE) {
                self::assertTrue($additionalData['is_preselected']);
                self::assertSame($imagePath, $additionalData['image']);
                self::assertNotNull($paymentMethod['multisafepay_available_issuers']);

                foreach ($paymentMethod['multisafepay_available_issuers'] as $issuer) {
                    self::assertArrayHasKey('code', $issuer);
                    self::assertArrayHasKey('description', $issuer);
                }
            }
        }
    }

    /**
     * @throws Exception
     */
    private function getQuery($maskedQuoteId)
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
