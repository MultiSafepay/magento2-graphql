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
use MultiSafepay\ConnectCore\CustomerData\PaymentRequest;
use Magento\Checkout\Model\Session as CheckoutSession;

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
     * @var PaymentRequest
     */
    private $paymentRequest;

    /**
     * @param CheckoutSession $checkoutSession
     * @param MaskedQuoteIdToQuoteId $maskedQuoteIdToQuoteId
     * @param PaymentRequest $paymentRequest
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        MaskedQuoteIdToQuoteId $maskedQuoteIdToQuoteId,
        PaymentRequest $paymentRequest
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->paymentRequest = $paymentRequest;
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
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null): array
    {
        $maskedCartId = $args['cart_id'];
        $cartId = $this->maskedQuoteIdToQuoteId->execute($maskedCartId);
        $this->checkoutSession->setQuoteId($cartId);
        return $this->paymentRequest->getSectionData();
    }
}
