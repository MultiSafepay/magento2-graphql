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

namespace MultiSafepay\ConnectGraphQl\Test\Functional;

use Exception;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

class BaseGraphQlTest extends GraphQlAbstract
{
    /**
     * @return ObjectManagerInterface
     */
    protected function getObjectManager(): ObjectManagerInterface
    {
        return Bootstrap::getObjectManager();
    }

    /**
     * @param string $fixtureFile
     * @throws Exception
     */
    protected function includeFixtureFile(string $fixtureFile): void
    {
        /** @var ComponentRegistrar $componentRegistrar */
        $componentRegistrar = $this->getObjectManager()->get(ComponentRegistrar::class);
        $modulePath = $componentRegistrar->getPath('module', 'MultiSafepay_ConnectGraphQl');
        $fixturePath = $modulePath . '/Test/Functional/_files/' . $fixtureFile . '.php';
        if (!is_file($fixturePath)) {
            throw new Exception('Fixture file "' . $fixturePath . '" could not be found');
        }

        $cwd = getcwd();
        $directoryList = $this->getObjectManager()->get(DirectoryList::class);
        $rootPath = $directoryList->getRoot();
        chdir($rootPath . '/dev/tests/integration/testsuite/');
        require($fixturePath);
        chdir($cwd);
    }
}
