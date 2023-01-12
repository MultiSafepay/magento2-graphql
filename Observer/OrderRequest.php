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

namespace MultiSafepay\ConnectGraphQl\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MultiSafepay\ConnectCore\Logger\Logger;
use Magento\Checkout\Model\Session as CheckoutSession;

class OrderRequest implements ObserverInterface
{
    /**
     * @var string
     */
    public const APPLICATION_VERSION_KEY = 'application_version';

    /**
     * @var string
     */
    public const PLUGIN_VERSION_KEY = 'plugin_version';

    /**
     * @var string
     */
    public const APPLICATION_NAME_KEY = 'application_name';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * OrderRequest constructor.
     *
     * @param CheckoutSession $checkoutSession
     * @param Logger $logger
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        Logger $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $pluginInfo = $this->getPluginInfo();

        if (empty($pluginInfo)) {
            return;
        }

        $orderRequest = $observer->getData('orderRequest');
        $pluginDetails = $orderRequest->getPluginDetails();

        if ($pluginDetails === null) {
            $order = $observer->getData('order');
            $this->logger->logInfoForOrder(
                $order->getIncrementId(),
                'Plugin details object not found, could not prepend plugin details'
            );

            return;
        }

        if (array_key_exists(self::APPLICATION_NAME_KEY, $pluginInfo)) {
            $applicationName = $pluginDetails->getApplicationName();
            $pluginDetails->addApplicationName($applicationName . ' - ' . $pluginInfo[self::APPLICATION_NAME_KEY]);
        }

        if (array_key_exists(self::PLUGIN_VERSION_KEY, $pluginInfo)) {
            $pluginVersion = $pluginDetails->getPluginVersion()->getPluginVersion();
            $pluginDetails->addPluginVersion($pluginVersion . ' - ' . $pluginInfo[self::PLUGIN_VERSION_KEY]);
        }

        if (array_key_exists(self::APPLICATION_VERSION_KEY, $pluginInfo)) {
            $applicationVersion = $pluginDetails->getApplicationVersion();
            $pluginDetails->addApplicationVersion(
                $applicationVersion . ' - ' . $pluginInfo[self::APPLICATION_VERSION_KEY]
            );
        }
    }

    /**
     * @return array
     */
    private function getPluginInfo(): array
    {
        $pluginInfo = $this->checkoutSession->getPluginInfo();

        return array_filter([
            self::APPLICATION_VERSION_KEY => $pluginInfo[self::APPLICATION_VERSION_KEY] ?? null,
            self::PLUGIN_VERSION_KEY => $pluginInfo[self::PLUGIN_VERSION_KEY] ?? null,
            self::APPLICATION_NAME_KEY => $pluginInfo[self::APPLICATION_NAME_KEY] ?? null,
        ]);
    }
}
