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

namespace MultiSafepay\ConnectGraphQl\Plugin\Resolver;

use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Url\EncoderInterface;
use Magento\Payment\Gateway\Config\Config;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectAdminhtml\Model\Config\Source\PaymentTypes;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\TransactionTypeBuilder;
use MultiSafepay\ConnectCore\Util\CustomReturnUrlUtil;
use MultiSafepay\ConnectCore\Model\SecureToken;
use MultiSafepay\ConnectCore\Util\OrderUtil;
use Magento\Framework\Exception\NoSuchEntityException;
use Exception;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PaymentUrl
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var CustomReturnUrlUtil
     */
    private $customReturnUrlUtil;

    /**
     * @var SecureToken
     */
    private $secureToken;

    /**
     * @var OrderUtil
     */
    private $orderUtil;

    /**
     * @var EncoderInterface
     */
    private $encoder;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Request $request
     * @param CustomReturnUrlUtil $customReturnUrlUtil
     * @param SecureToken $secureToken
     * @param OrderUtil $orderUtil
     * @param EncoderInterface $encoder
     * @param Config $config
     */
    public function __construct(
        Request $request,
        CustomReturnUrlUtil $customReturnUrlUtil,
        SecureToken $secureToken,
        OrderUtil $orderUtil,
        EncoderInterface $encoder,
        Config $config
    ) {
        $this->request = $request;
        $this->customReturnUrlUtil = $customReturnUrlUtil;
        $this->secureToken = $secureToken;
        $this->orderUtil = $orderUtil;
        $this->encoder = $encoder;
        $this->config = $config;
    }

    /**
     * @param \MultiSafepay\ConnectGraphQl\Model\Resolver\PaymentUrl $subject
     * @param array|null $resolverResult
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|null
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function afterResolve(
        \MultiSafepay\ConnectGraphQl\Model\Resolver\PaymentUrl $subject,
        ?array $resolverResult,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): ?array {
        if (array_key_exists('error', $resolverResult) && $resolverResult['error']
            || $this->config->getValue('payment_type') !== PaymentTypes::PAYMENT_COMPONENT_PAYMENT_TYPE
        ) {
            return $resolverResult;
        }

        try {
            $order = $this->orderUtil->getOrderByIncrementId($value['order_number']);
        } catch (NoSuchEntityException $exception) {
            return $resolverResult;
        }

        /** @var InfoInterface $payment */
        $payment = $order->getPayment();
        $transactionType = $payment->getAdditionalInformation('transaction_type');

        if ($transactionType !== TransactionTypeBuilder::TRANSACTION_TYPE_DIRECT_VALUE) {
            return $resolverResult;
        }

        $customReturnUrl = $this->customReturnUrlUtil->getCustomReturnUrlByType(
            $order,
            $this->getParameters($order),
            CustomReturnUrlUtil::SUCCESS_URL_TYPE_NAME
        );

        if (!$customReturnUrl) {
            return $resolverResult;
        }

        $resolverResult['payment_url'] = $customReturnUrl;

        return $resolverResult;
    }

    /**
     * @param Order $order
     *
     * @return array
     */
    private function getParameters(Order $order): array
    {
        try {
            return [
                'secureToken' => $this->secureToken->generate((string)$order->getRealOrderId()),
                'application_url' => $this->encoder->encode($this->getApplicationUrl()),
            ];
        } catch (Exception $exception) {
            return [];
        }
    }

    /**
     * @return string|null
     */
    private function getApplicationUrl(): ?string
    {
        return $this->request->getServerValue('HTTP_ORIGIN') ?? null;
    }
}
