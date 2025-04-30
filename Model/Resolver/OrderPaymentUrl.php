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

namespace MultiSafepay\ConnectGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;

class OrderPaymentUrl implements ResolverInterface
{
    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * OrderPaymentUrl constructor.
     *
     * @param PaymentMethodUtil $paymentMethodUtil
     */
    public function __construct(
        PaymentMethodUtil $paymentMethodUtil
    ) {
        $this->paymentMethodUtil = $paymentMethodUtil;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ): ?string {
        if (!isset($value['model']) && !($value['model'] instanceof Order)) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        $order = $value['model'];

        if ($this->paymentMethodUtil->isMultisafepayOrder($order)) {
            return (string)$order->getPayment()->getAdditionalInformation('payment_link');
        }

        return '';
    }
}
