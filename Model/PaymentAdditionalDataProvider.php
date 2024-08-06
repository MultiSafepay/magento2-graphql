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

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Payment\Gateway\Config\Config;
use Magento\QuoteGraphQl\Model\Cart\Payment\AdditionalDataProviderInterface;
use MultiSafepay\Api\Issuers\Issuer;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\IdealConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\MyBankConfigProvider;
use Psr\Http\Client\ClientExceptionInterface;

class PaymentAdditionalDataProvider implements AdditionalDataProviderInterface
{
    /**
     * @var IdealConfigProvider
     */
    private $idealConfigProvider;

    /**
     * @var MyBankConfigProvider
     */
    private $myBankConfigProvider;

    /**
     * @var Config
     */
    private $paymentConfig;

    /**
     * @var string
     */
    private $providerCode;

    /**
     * PaymentAdditionalDataProvider constructor.
     *
     * @param IdealConfigProvider $idealConfigProvider
     * @param MyBankConfigProvider $myBankConfigProvider
     * @param string $providerCode
     */
    public function __construct(
        IdealConfigProvider $idealConfigProvider,
        MyBankConfigProvider $myBankConfigProvider,
        Config $paymentConfig,
        $providerCode = ''
    ) {
        $this->idealConfigProvider = $idealConfigProvider;
        $this->myBankConfigProvider = $myBankConfigProvider;
        $this->paymentConfig = $paymentConfig;
        $this->providerCode = $providerCode;
    }

    /**
     * @param array $data
     * @return array
     * @throws ClientExceptionInterface
     * @throws GraphQlInputException
     */
    public function getData(array $data): array
    {
        $this->paymentConfig->setMethodCode($this->providerCode);
        if (!isset($data[$this->providerCode]) && ($this->paymentConfig->getValue('payment_type') ?? $this->paymentConfig->getValue('transaction_type') ?? 'undefined') !== 'redirect') {
            throw new GraphQlInputException(
                __(
                    'Required parameter "%1" for "payment_method" is missing.',
                    $this->providerCode
                )
            );
        }

        $additionalData = $data[$this->providerCode] ?? [];

        if ($this->providerCode === IdealConfigProvider::CODE || $this->providerCode === MyBankConfigProvider::CODE) {
            $this->validateIssuerId($additionalData);
        }

        return $additionalData;
    }

    /**
     * @param array $data
     * @throws GraphQlInputException
     * @throws ClientExceptionInterface
     */
    private function validateIssuerId(array $data): void
    {
        $issuerId = $data['issuer_id'] ?? null;

        if (!$issuerId) {
            return;
        }

        $issuers = [];

        if ($this->providerCode === IdealConfigProvider::CODE) {
            $issuers = $this->idealConfigProvider->getIssuers();
        }

        if ($this->providerCode === MyBankConfigProvider::CODE) {
            $issuers = $this->myBankConfigProvider->getIssuers();
        }

        if (!in_array($issuerId, array_column($issuers, 'code'), true)) {
            throw new GraphQlInputException(__('Please check and set the correct Issuer ID.'));
        }
    }
}
