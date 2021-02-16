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

namespace MultiSafepay\ConnectGraphQl\Model;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\QuoteGraphQl\Model\Cart\Payment\AdditionalDataProviderInterface;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\IdealConfigProvider;
use Psr\Http\Client\ClientExceptionInterface;

class PaymentAdditionalDataProvider implements AdditionalDataProviderInterface
{
    /**
     * @var IdealConfigProvider
     */
    private $idealConfigProvider;

    /**
     * @var string
     */
    private $providerCode;

    /**
     * PaymentAdditionalDataProvider constructor.
     *
     * @param IdealConfigProvider $idealConfigProvider
     * @param string $providerCode
     */
    public function __construct(
        IdealConfigProvider $idealConfigProvider,
        $providerCode = ''
    ) {
        $this->idealConfigProvider = $idealConfigProvider;
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
        if (!isset($data[$this->providerCode])) {
            throw new GraphQlInputException(
                __(
                    'Required parameter "%1" for "payment_method" is missing.',
                    $this->providerCode
                )
            );
        }

        $additionalData = $data[$this->providerCode];

        if ($this->providerCode === IdealConfigProvider::CODE) {
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

        if ($issuerId) {
            $allIssuers = $this->idealConfigProvider->getIssuers();

            if (!in_array($issuerId, array_column($allIssuers, 'code'), true)) {
                throw new GraphQlInputException(__('Please check and set the correct Issuer ID.'));
            }
        }
    }
}
