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

namespace MultiSafepay\ConnectGraphQl\Plugin\Resolver;

use Magento\Framework\App\Area;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Model\App\Emulation;
use MultiSafepay\ConnectCore\Model\Ui\ConfigProviderPool;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;

class SaveAddressInformationPlugin
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
     * SaveAddressInformationPlugin constructor.
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
        $this->emulation = $emulation;
        $this->paymentMethodUtil = $paymentMethodUtil;
    }

    /**
     * @param \ScandiPWA\QuoteGraphQl\Model\Resolver\SaveAddressInformation $subject
     * @param $resolverResult
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return mixed|Value
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @throws NoSuchEntityException
     */
    public function afterResolve(
        \ScandiPWA\QuoteGraphQl\Model\Resolver\SaveAddressInformation $subject,
        $resolverResult,
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        if ($resolverResult && is_array($resolverResult) && isset($resolverResult['payment_methods'])) {
            $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
            $paymentMethods = $resolverResult['payment_methods'];
            $this->emulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);

            foreach ($paymentMethods as $key => $paymentMethod) {
                $code = $paymentMethod['code'];
                $paymentMethods[$key]['multisafepay_additional_data'] = [];
                $paymentMethods[$key]['multisafepay_available_issuers'] = [];

                if (!$code || !$this->paymentMethodUtil->isMultisafepayPaymentByCode($code)) {
                    continue;
                }

                if ($configProvider = $this->configProviderPool->getConfigProviderByCode($code)) {
                    $config = $configProvider->getConfig();
                    if (isset($config['payment'][$code]['image'], $config['payment'][$code]['is_preselected'])) {
                        $paymentMethods[$key]['multisafepay_additional_data'] = $config['payment'][$code];
                    }

                    $paymentMethods[$key]['multisafepay_available_issuers']
                        = method_exists($configProvider, 'getIssuers')
                        ? $configProvider->getIssuers() : [];
                }
            }

            $this->emulation->stopEnvironmentEmulation();
            $resolverResult['payment_methods'] = $paymentMethods;
        }

        return $resolverResult;
    }
}
