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

namespace MultiSafepay\ConnectGraphQl\Model\Resolver\Issuer;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MultiSafepay\ConnectCore\Model\Ui\ConfigProviderPool;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;

class AvailableIssuersForPaymentMethod implements ResolverInterface
{
    /**
     * @var ConfigProviderPool
     */
    private $configProviderPool;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * AvailableIssuersForPaymentMethod constructor.
     *
     * @param ConfigProviderPool $configProviderPool
     * @param PaymentMethodUtil $paymentMethodUtil
     */
    public function __construct(
        ConfigProviderPool $configProviderPool,
        PaymentMethodUtil $paymentMethodUtil
    ) {
        $this->configProviderPool = $configProviderPool;
        $this->paymentMethodUtil = $paymentMethodUtil;
    }

    /**
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|null
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): ?array {
        $method = $value['code'];

        if (!$method || !$this->paymentMethodUtil->isMultisafepayPaymentByCode($method)) {
            return null;
        }

        if ($configProvider = $this->configProviderPool->getConfigProviderByCode($method)) {
            return method_exists($configProvider, 'getIssuers')
                ? $configProvider->getIssuers() : null;
        }

        return null;
    }
}
