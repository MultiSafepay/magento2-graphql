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
 * Copyright © 2022 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\MaskedQuoteIdToQuoteId;
use MultiSafepay\ConnectCore\Model\Ui\GenericConfigProvider;
use Magento\Checkout\Model\Session as CheckoutSession;
use MultiSafepay\ConnectCore\Config\Config;
use Magento\Framework\Locale\ResolverInterface as LocaleResolverInterface;
use MultiSafepay\ConnectGraphQl\Model\PaymentConfig;

class PaymentComponentData implements ResolverInterface
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var MaskedQuoteIdToQuoteId
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var LocaleResolverInterface
     */
    private $localeResolver;

    /**
     * @var GenericConfigProvider
     */
    private $genericConfigProvider;

    /**
     * @var PaymentConfig
     */
    private $paymentConfig;

    /**
     * @param CheckoutSession $checkoutSession
     * @param MaskedQuoteIdToQuoteId $maskedQuoteIdToQuoteId
     * @param Config $config
     * @param LocaleResolverInterface $localeResolver
     * @param GenericConfigProvider $genericConfigProvider
     * @param PaymentConfig $paymentConfig
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        MaskedQuoteIdToQuoteId $maskedQuoteIdToQuoteId,
        Config $config,
        LocaleResolverInterface $localeResolver,
        GenericConfigProvider $genericConfigProvider,
        PaymentConfig $paymentConfig
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->config = $config;
        $this->localeResolver = $localeResolver;
        $this->genericConfigProvider = $genericConfigProvider;
        $this->paymentConfig = $paymentConfig;
    }

    /**
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     *
     * @return array
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null): array
    {
        $maskedCartId = $args['cart_id'];
        $cartId = $this->maskedQuoteIdToQuoteId->execute($maskedCartId);
        $this->checkoutSession->setQuoteId($cartId);
        $sectionData = $this->getSectionData();

        return $sectionData;
    }

    /**
     * @return array|false[]
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getSectionData(): array
    {
        try {
            $quote = $this->checkoutSession->getQuote();
            $storeId = (int) $quote->getStoreId();
            $result = [
                "enabled" => false,
                "environment" => $this->config->isLiveMode($storeId) ? 'live' : 'test',
                "locale" => $this->localeResolver->getLocale(),
                "cartTotal" => $quote->getGrandTotal(),
                "currency" => $this->paymentConfig->getCurrency(),
            ];

            if ($cardsConfig = $this->paymentConfig->getCardsConfig($storeId)) {
                $result = array_merge(
                    $result,
                    [
                        "enabled" => true,
                        "cardsConfig" => $cardsConfig,
                        'apiToken' => $this->genericConfigProvider->getApiToken($storeId)
                    ]
                );
            }
            $result['isDebug'] = $this->config->isDebug($storeId);
        } catch (Exception $exception) {
            $this->logger->logPaymentRequestGetCustomerDataException($exception);
            $result = $result ?? ["enabled" => false];
        }

        return $result;
    }
}
