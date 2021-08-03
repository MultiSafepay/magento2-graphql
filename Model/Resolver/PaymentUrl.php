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

namespace MultiSafepay\ConnectGraphQl\Model\Resolver;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\PaymentLink;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\Exception\InvalidApiKeyException;
use Psr\Http\Client\ClientExceptionInterface;

class PaymentUrl implements ResolverInterface
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var PaymentLink
     */
    private $paymentLink;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * PaymentUrl constructor.
     *
     * @param Session $checkoutSession
     * @param PaymentLink $paymentLink
     * @param Logger $logger
     * @param PaymentMethodUtil $paymentMethodUtil
     */
    public function __construct(
        Session $checkoutSession,
        PaymentLink $paymentLink,
        Logger $logger,
        PaymentMethodUtil $paymentMethodUtil
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->paymentLink = $paymentLink;
        $this->logger = $logger;
        $this->paymentMethodUtil = $paymentMethodUtil;
    }

    /**
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
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
        $order = $this->checkoutSession->getLastRealOrder();
        $orderId = $order->getRealOrderId();
        $result = [
            'payment_url' => '',
            'error' => ''
        ];

        if ($orderId && $this->paymentMethodUtil->isMultisafepayOrder($order)) {
            try {
                $paymentUrl = $this->paymentLink->getPaymentLinkByOrder($order);
                $this->logger->logPaymentRedirectInfo($orderId, $paymentUrl);
                $this->paymentLink->addPaymentLink($order, $paymentUrl);
                $result['payment_url'] = $paymentUrl;
            } catch (InvalidApiKeyException $invalidApiKeyException) {
                $this->logger->logInvalidApiKeyException($invalidApiKeyException);
                $result['error'] = $invalidApiKeyException->getMessage();
            } catch (ApiException $apiException) {
                $this->logger->logPaymentLinkError($orderId, $apiException);
                $result['error'] = $apiException->getMessage();
            } catch (Exception $exception) {
                $this->logger->logExceptionForOrder($orderId, $exception);
                $result['error'] = $exception->getMessage();
            } catch (ClientExceptionInterface $clientException) {
                $this->logger->logExceptionForOrder($orderId, $clientException);
                $result['error'] = $clientException->getMessage();
            }
        }

        return $result;
    }
}
