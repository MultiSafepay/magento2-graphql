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

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MultiSafepay\ConnectGraphQl\Observer\OrderRequest;

class PlaceOrder
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(CheckoutSession $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param \Magento\QuoteGraphQl\Model\Resolver\PlaceOrder $subject
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function beforeResolve(
        \Magento\QuoteGraphQl\Model\Resolver\PlaceOrder $subject,
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = [],
        ?array $args = []
    ): array {
        if (!$args) {
            return [$field, $context, $info, $value, $args];
        }

        $pluginInfo = [
            OrderRequest::PLUGIN_VERSION_KEY => $args['input'][OrderRequest::PLUGIN_VERSION_KEY] ?? null,
            OrderRequest::APPLICATION_VERSION_KEY => $args['input'][OrderRequest::APPLICATION_VERSION_KEY] ?? null,
            OrderRequest::APPLICATION_NAME_KEY => $args['input'][OrderRequest::APPLICATION_NAME_KEY] ?? null,
        ];

        $this->checkoutSession->setPluginInfo($pluginInfo);

        return [$field, $context, $info, $value, $args];
    }
}
