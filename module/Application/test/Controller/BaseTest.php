<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ApplicationTest\Controller;

use Application\Model\BaseTable;
use Zend\Stdlib\ArrayUtils;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Zend\ServiceManager\ServiceManager;
use Zend\Mvc\Service;

class BaseTest extends AbstractHttpControllerTestCase {
    /**
     * 
     * @var ServiceManager
     */
    private static $serviceManager;

    /**
     * @return BaseTable
     */
    protected function getTableModel($name) {
        return self::$serviceManager->get($name);
    }

    protected function getServiceManager() {
        return self::$serviceManager;
    }

    public function setUp() {
        error_reporting(E_ALL & ~E_NOTICE);
        defined('TEST_ENV') || define('TEST_ENV', true);
        $configOverrides = [];
        defined('ZF_PATH') || define('ZF_PATH', str_replace('\\', '/', realpath(__DIR__ . '/../../../..')));
        chdir(ZF_PATH);
        $configuration = ArrayUtils::merge(
            include ZF_PATH . '/config/application.config.php',
            $configOverrides
        );
        $this->setApplicationConfig($configuration);

        $smConfig = isset($configuration['service_manager']) ? $configuration['service_manager'] : [];
        $smConfig = new Service\ServiceManagerConfig($smConfig);

        $serviceManager = new ServiceManager();
        $smConfig->configureServiceManager($serviceManager);
        $serviceManager->setService('ApplicationConfig', $configuration);

        // Load modules
        $serviceManager->get('ModuleManager')->loadModules();
        self::$serviceManager = $serviceManager;

        parent::setUp();
    }
}
