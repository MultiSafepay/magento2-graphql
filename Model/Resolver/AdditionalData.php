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

namespace MultiSafepay\ConnectGraphQl\Model\Resolver;

use Magento\Framework\App\Area;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Model\App\Emulation;
use MultiSafepay\ConnectCore\Model\Ui\ConfigProviderPool;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;

class AdditionalData implements ResolverInterface
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
     * @var Emulation
     */
    private $emulation;

    /**
     * AdditionalData constructor.
     *
     * @param ConfigProviderPool $configProviderPool
     * @param PaymentMethodUtil $paymentMethodUtil
     * @param Emulation $emulation
     */
    public function __construct(
        ConfigProviderPool $configProviderPool,
        PaymentMethodUtil $paymentMethodUtil,
        Emulation $emulation
    ) {
        $this->configProviderPool = $configProviderPool;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->emulation = $emulation;
    }

    /**
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return null[]
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        $method = $value['code'];
        $result = [
            'image' => null,
            'is_preselected' => null
        ];

        if (!$method || !$this->paymentMethodUtil->isMultisafepayPaymentByCode($method)) {
            return $result;
        }

        if ($configProvider = $this->configProviderPool->getConfigProviderByCode($method)) {
            $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
            $this->emulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
            $config = $configProvider->getConfig();
            $this->emulation->stopEnvironmentEmulation();

            if (isset($config['payment'][$method]['image'], $config['payment'][$method]['is_preselected'])) {
                return $config['payment'][$method];
            }
        }

        return $result;
    }
}
