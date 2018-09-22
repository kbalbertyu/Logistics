<?php
namespace User;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Application\Model\BaseTable;

class Module implements ConfigProviderInterface
{

    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    public function getServiceConfig()
    {
        return [
            'factories' => [
                Model\UserTable::class => function ($container) {
                    $tableGateway = $container->get(Model\UserTableGateway::class);
                    return new Model\UserTable($tableGateway);
                },
                Model\UserTableGateway::class => function ($container) {
                    $dbAdapter = $container->get('Db\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Model\User());
                    return new TableGateway(BaseTable::USER_TABLE, $dbAdapter, null, $resultSetPrototype);
                },
            ]
        ];
    }

    public function getControllerConfig()
    {
        return [
            'factories' => [
                Controller\UserController::class => function ($container) {
                    return new Controller\UserController($container);
                },
                Controller\UserController::class => function ($container) {
                    return new Controller\UserController($container);
                },
                Controller\ApiController::class => function ($container) {
                    return new Controller\ApiController($container);
                }
            ]
        ];
    }
}