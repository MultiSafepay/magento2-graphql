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

use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\PaymentInterfaceFactory;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\TestFramework\Helper\Bootstrap;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\BancontactConfigProvider;

/** @var QuoteFactory $quoteFactory */
$quoteFactory = Bootstrap::getObjectManager()->get(QuoteFactory::class);
/** @var QuoteResource $quoteResource */
$quoteResource = Bootstrap::getObjectManager()->get(QuoteResource::class);
/** @var PaymentInterfaceFactory $paymentFactory */
$paymentFactory = Bootstrap::getObjectManager()->get(PaymentInterfaceFactory::class);
/** @var PaymentMethodManagementInterface $paymentMethodManagement */
$paymentMethodManagement = Bootstrap::getObjectManager()->get(PaymentMethodManagementInterface::class);

$quote = $quoteFactory->create();
$quoteResource->load($quote, 'test_quote', 'reserved_order_id');

$payment = $paymentFactory->create([
    'data' => [
        PaymentInterface::KEY_METHOD => BancontactConfigProvider::CODE,
    ]
]);
$paymentMethodManagement->set($quote->getId(), $payment);
