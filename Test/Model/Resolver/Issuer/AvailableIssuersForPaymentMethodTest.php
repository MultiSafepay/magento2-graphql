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

namespace MultiSafepay\ConnectGraphQl\Test\Model\Resolver\Issuer;

use Exception;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\App\Emulation;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\IdealConfigProvider;
use MultiSafepay\ConnectCore\Config\Config as MultiSafepayConfig;

class AvailableIssuersForPaymentMethodTest extends GraphQlAbstract
{
    /**
     * Will be used if Test Api Key in config has been left empty
     */
    public const FUNCTIONAL_TEST_API_KEY = '';

    /**
     * @var string
     */
    private $defaultPreselectedSetting;

    /**
     * @var string
     */
    private $defaultTestApiKeySetting;

    /**
     * @var string
     */
    private $defaultApiModeSetting;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $scopeConfig = $this->getScopeConfig();
        $config = $this->getConfig();

        $preselectedSettingPath = $this->getPreselectedPath();
        $this->defaultPreselectedSetting = $scopeConfig->getValue($preselectedSettingPath);

        $config->saveConfig(
            $preselectedSettingPath,
            IdealConfigProvider::CODE
        );

        $testApiModePath = $this->getApiModePath();
        $this->defaultApiModeSetting = $scopeConfig->getValue($testApiModePath);

        $config->saveConfig(
            $testApiModePath,
            0
        );

        $testApiKeyPath = $this->getTestApiKeyPath();
        $this->defaultTestApiKeySetting = $scopeConfig->getValue($testApiKeyPath);

        if (!$scopeConfig->getValue($testApiKeyPath)) {
            $config->saveConfig($testApiKeyPath, self::FUNCTIONAL_TEST_API_KEY);
        }

        $this->cleanConfig();
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        $config = $this->getConfig();
        $config->saveConfig($this->getPreselectedPath(), $this->defaultPreselectedSetting);
        $config->saveConfig($this->getApiModePath(), $this->defaultApiModeSetting);
        $config->saveConfig($this->getTestApiKeyPath(), $this->defaultTestApiKeySetting);

        $this->cleanConfig();
    }

    /**
     * @throws Exception
     */
    public function testAvailablePaymentMethodsQueryWithIssuersAndAdditionalData(): void
    {
        $cartId = $this->createEmptyGuestCart();

        $query = $this->getQuery($cartId);
        $response = $this->graphQlQuery($query);

        self::assertArrayHasKey('cart', $response);
        self::assertNotEmpty($response['cart']);

        self::assertArrayHasKey('available_payment_methods', $response['cart']);
        self::assertNotEmpty($response['cart']['available_payment_methods']);

        $emulation = $this->getEmulation();
        $emulation->startEnvironmentEmulation(1, Area::AREA_FRONTEND, true);
        $imagePath = $this->getIdealConfigProvider()->getImage();
        $emulation->stopEnvironmentEmulation();

        foreach ($response['cart']['available_payment_methods'] as $paymentMethod) {
            self::assertArrayHasKey('code', $paymentMethod);
            self::assertArrayHasKey('title', $paymentMethod);
            self::assertArrayHasKey('multisafepay_available_issuers', $paymentMethod);
            self::assertArrayHasKey('multisafepay_additional_data', $paymentMethod);

            $additionalData = $paymentMethod['multisafepay_additional_data'];

            self::assertArrayHasKey('image', $additionalData);
            self::assertArrayHasKey('is_preselected', $additionalData);

            if ($paymentMethod['code'] === IdealConfigProvider::CODE) {
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
     * @return string
     * @throws Exception
     */
    private function createEmptyGuestCart(): string
    {
        $query = <<<QUERY
mutation {
  createEmptyCart
}
QUERY;
        $response = $this->graphQlMutation($query);

        self::assertArrayHasKey('createEmptyCart', $response);
        self::assertIsString($response['createEmptyCart']);

        return $response['createEmptyCart'];
    }

    /**
     * @param string $cartId
     * @return string
     */
    private function getQuery(string $cartId): string
    {
        return <<<QUERY
query {
    cart(cart_id: "$cartId") {
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
QUERY;
    }

    /**
     * @return Config
     */
    private function getConfig(): Config
    {
        return $this->objectManager->get(Config::class);
    }

    /**
     * @return IdealConfigProvider
     */
    private function getIdealConfigProvider(): IdealConfigProvider
    {
        return $this->objectManager->get(IdealConfigProvider::class);
    }

    /**
     * @return Emulation
     */
    private function getEmulation(): Emulation
    {
        return $this->objectManager->get(Emulation::class);
    }

    /**
     * @return ScopeConfigInterface
     */
    private function getScopeConfig(): ScopeConfigInterface
    {
        return $this->objectManager->get(ScopeConfigInterface::class);
    }

    /**
     * @return string
     */
    private function getPreselectedPath(): string
    {
        return sprintf(
            MultiSafepayConfig::DEFAULT_PATH_PATTERN,
            MultiSafepayConfig::PRESELECTED_METHOD
        );
    }

    /**
     * @return string
     */
    private function getTestApiKeyPath(): string
    {
        return sprintf(
            MultiSafepayConfig::DEFAULT_PATH_PATTERN,
            MultiSafepayConfig::TEST_API_KEY
        );
    }

    /**
     * @return string
     */
    private function getApiModePath(): string
    {
        return sprintf(
            MultiSafepayConfig::DEFAULT_PATH_PATTERN,
            MultiSafepayConfig::API_MODE
        );
    }

    /**
     * @return void
     */
    private function cleanConfig(): void
    {
        /** @var ReinitableConfigInterface $config */
        $config = $this->objectManager->get(ReinitableConfigInterface::class);
        $config->reinit();

        /** @var ScopeConfigInterface $scopeConfig */
        $scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $scopeConfig->clean();
    }
}
