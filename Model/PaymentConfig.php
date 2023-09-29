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

namespace MultiSafepay\ConnectGraphQl\Model;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use MultiSafepay\ConnectAdminhtml\Model\Config\Source\PaymentTypes;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\AmexConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\CreditCardConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\MaestroConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\MastercardConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\VisaConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\ConfigProviderPool;

class PaymentConfig
{
    /**
     * @var array
     */
    private const CREDIT_CARD_PAYMENT_METHODS = [
        AmexConfigProvider::CODE,
        MaestroConfigProvider::CODE,
        MastercardConfigProvider::CODE,
        VisaConfigProvider::CODE,
        CreditCardConfigProvider::CODE,
    ];

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var ConfigProviderPool
     */
    private $configProviderPool;

    /**
     * @param CheckoutSession $checkoutSession
     * @param ConfigProviderPool $configProviderPool
     */
    public function __construct(CheckoutSession $checkoutSession, ConfigProviderPool $configProviderPool)
    {
        $this->checkoutSession = $checkoutSession;
        $this->configProviderPool = $configProviderPool;
    }

    /**
     * @return string
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCurrency(): string
    {
        $quote = $this->checkoutSession->getQuote();
        $currency = $quote->getCurrency();

        return (string)($currency ? $currency->getQuoteCurrencyCode() : '');
    }

    /**
     * @param int $storeId
     *
     * @return array
     */
    public function getCardsConfig(int $storeId): array
    {
        $result = [];

        foreach (self::CREDIT_CARD_PAYMENT_METHODS as $methodCode) {
            if (!($configProvider = $this->configProviderPool->getConfigProviderByCode($methodCode))) {
                continue;
            }

            $additionalDataConfig = $configProvider->getConfig();
            $paymentConfig = $configProvider->getPaymentConfig($storeId);

            if ($paymentConfig && $this->isPaymentComponentAvailable($paymentConfig)) {
                $result[$methodCode] = [
                    "paymentMethod" => $methodCode,
                    "gatewayCode" => $paymentConfig['gateway_code'],
                    "paymentType" => $paymentConfig['payment_type'],
                    "additionalInfo" => $additionalDataConfig && isset($additionalDataConfig['payment'][$methodCode])
                        ? $additionalDataConfig['payment'][$methodCode] : [],
                ];

                if ((bool)($paymentConfig['tokenization'] ?? null) === true) {
                    $result[$methodCode]['customerReference'] = $this->getCustomerReference($paymentConfig);
                }
            }
        }

        return $result;
    }

    /**
     * @param array $paymentConfig
     * @return bool
     */
    public function isPaymentComponentAvailable(array $paymentConfig): bool
    {
        return isset($paymentConfig['payment_type'], $paymentConfig['active'])
            && $paymentConfig['payment_type'] === PaymentTypes::PAYMENT_COMPONENT_PAYMENT_TYPE
            && (bool)$paymentConfig['active'];
    }

    /**
     * @param array $paymentConfig
     *
     * @return int|null
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCustomerReference(array $paymentConfig): ?int
    {
        $quote = $this->checkoutSession->getQuote();

        if ($quote->getCustomerIsGuest()) {
            return null;
        }

        if (!array_key_exists('tokenization', $paymentConfig)) {
            return null;
        }

        if (!$paymentConfig['tokenization']) {
            return null;
        }

        return (int)$quote->getCustomer()->getId();
    }
}
