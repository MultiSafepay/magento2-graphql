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

use Exception;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\QuoteGraphQl\Model\Cart\Payment\AdditionalDataProviderInterface;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\MyBankConfigProvider;
use Psr\Http\Client\ClientExceptionInterface;

class PaymentAdditionalDataProvider implements AdditionalDataProviderInterface
{
    /**
     * @var MyBankConfigProvider
     */
    private $myBankConfigProvider;

    /**
     * @var string
     */
    private $providerCode;

    /**
     * PaymentAdditionalDataProvider constructor.
     *
     * @param MyBankConfigProvider $myBankConfigProvider
     * @param string $providerCode
     */
    public function __construct(
        MyBankConfigProvider $myBankConfigProvider,
        $providerCode = ''
    ) {
        $this->myBankConfigProvider = $myBankConfigProvider;
        $this->providerCode = $providerCode;
    }

    /**
     * @param array $data
     * @return array
     * @throws GraphQlInputException
     */
    public function getData(array $data): array
    {
        $additionalData = $data[$this->providerCode] ?? [];

        if ($this->providerCode === MyBankConfigProvider::CODE) {
            $this->validateIssuerId($additionalData);
        }

        return $additionalData;
    }

    /**
     * @param array $data
     * @throws GraphQlInputException
     * @throws Exception
     */
    private function validateIssuerId(array $data): void
    {
        $issuerId = $data['issuer_id'] ?? null;

        if (!$issuerId) {
            return;
        }

        $issuers = [];

        if ($this->providerCode === MyBankConfigProvider::CODE) {
            $issuers = $this->myBankConfigProvider->getIssuers();
        }

        if (!in_array($issuerId, array_column($issuers, 'code'), true)) {
            throw new GraphQlInputException(__('Please check and set the correct Issuer ID.'));
        }
    }
}
