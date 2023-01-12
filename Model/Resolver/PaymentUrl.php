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

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\PaymentLink;
use MultiSafepay\ConnectCore\Util\OrderUtil;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;

class PaymentUrl implements ResolverInterface
{
    /**
     * @var PaymentLink
     */
    private $paymentLink;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var OrderUtil
     */
    private $orderUtil;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param PaymentLink $paymentLink
     * @param PaymentMethodUtil $paymentMethodUtil
     * @param OrderUtil $orderUtil
     * @param Logger $logger
     */
    public function __construct(
        PaymentLink $paymentLink,
        PaymentMethodUtil $paymentMethodUtil,
        OrderUtil $orderUtil,
        Logger $logger
    ) {
        $this->paymentLink = $paymentLink;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->orderUtil = $orderUtil;
        $this->logger = $logger;
    }

    /**
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     *
     * @return string[]|null
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): ?array {
        $result = [
            'payment_url' => '',
            'error' => ''
        ];

        if (!array_key_exists('order_number', $value)) {
            return $result;
        }

        try {
            $order = $this->orderUtil->getOrderByIncrementId($value['order_number']);
        } catch (NoSuchEntityException $noSuchEntityException) {
            $this->logger->logException($noSuchEntityException);
            return $result;
        }

        $orderId = $order->getEntityId();

        if ($orderId && $this->paymentMethodUtil->isMultisafepayOrder($order)) {
            $paymentUrl = $this->paymentLink->getPaymentLinkFromOrder($order);
            $result['payment_url'] = $paymentUrl;
            $result['error'] = $paymentUrl ? '' : 'Something went wrong. Please, check the logs.';
        }

        return $result;
    }
}
