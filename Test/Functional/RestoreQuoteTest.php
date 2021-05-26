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
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\ObjectManagerInterface;
use Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQl\ResponseContainsErrorsException;
use Magento\TestFramework\TestCase\GraphQlAbstract;

class RestoreQuoteTest extends GraphQlAbstract
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
        $objectManager = $this->getObjectManager();
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
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/make_cart_inactive.php
     *
     * @throws Exception
     */
    public function testRestoreInactiveQuoteWithFalseMaskedQuoteId(): void
    {
        $maskedQuoteId = 'not-existing-masked-quote-id';
        $this->includeFixtureFile('set_bancontact_payment_method');

        $this->expectException(ResponseContainsErrorsException::class);
        $this->expectExceptionMessage('Could not find a cart with ID ' . "\"$maskedQuoteId\"");
        $this->getMutation($maskedQuoteId);
    }

    /**
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/set_guest_email.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_new_shipping_address.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_new_billing_address.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_flatrate_shipping_method.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/set_checkmo_payment_method.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/make_cart_inactive.php
     *
     * @throws Exception
     */
    public function testRestoreInactiveQuoteWithoutMultiSafepayPaymentMethod(): void
    {
        $reservedOrderId = 'test_quote';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute($reservedOrderId);

        $this->expectException(ResponseContainsErrorsException::class);
        $this->expectExceptionMessage('This cart ' . "\"$maskedQuoteId\""
                                      . ' is not using a MultiSafepay payment method');
        $this->getMutation($maskedQuoteId);
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
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/make_cart_inactive.php
     *
     * @throws Exception
     */
    public function testRestoreInactiveQuoteWithMultiSafepayPaymentMethod(): void
    {
        $reservedOrderId = 'test_quote';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute($reservedOrderId);
        $this->includeFixtureFile('set_bancontact_payment_method');

        $response = $this->getMutation($maskedQuoteId);

        self::assertArrayHasKey('restoreQuote', $response);
        self::assertSame($maskedQuoteId, $response['restoreQuote']);
    }

    /**
     * @throws Exception
     */
    private function getMutation($maskedQuoteId)
    {
        return $this->graphQlMutation(
            <<<QUERY
    mutation {
        restoreQuote(input: {cart_id: "$maskedQuoteId"} )
    }
QUERY
        );
    }

    /**
     * @return ObjectManagerInterface
     */
    protected function getObjectManager(): ObjectManagerInterface
    {
        return Bootstrap::getObjectManager();
    }

    /**
     * @param string $fixtureFile
     * @throws Exception
     */
    protected function includeFixtureFile(string $fixtureFile): void
    {
        /** @var ComponentRegistrar $componentRegistrar */
        $componentRegistrar = $this->getObjectManager()->get(ComponentRegistrar::class);
        $modulePath = $componentRegistrar->getPath('module', 'MultiSafepay_ConnectGraphQl');
        $fixturePath = $modulePath . '/Test/Functional/_files/' . $fixtureFile . '.php';
        if (!is_file($fixturePath)) {
            throw new Exception('Fixture file "' . $fixturePath . '" could not be found');
        }

        $cwd = getcwd();
        $directoryList = $this->getObjectManager()->get(DirectoryList::class);
        $rootPath = $directoryList->getRoot();
        chdir($rootPath . '/dev/tests/integration/testsuite/');
        require($fixturePath);
        chdir($cwd);
    }
}
