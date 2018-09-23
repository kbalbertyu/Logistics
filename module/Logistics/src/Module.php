<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/10/2018
 * Time: 10:20 PM
 */

namespace Logistics;


use Application\Model\BaseTable;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\ModuleManager\Feature\ConfigProviderInterface;

class Module implements ConfigProviderInterface {

    public function getConfig() {
        return include __DIR__ . '/../config/module.config.php';
    }

    public function getServiceConfig() {
        return [
            'factories' => [
                Model\TeamTable::class => function ($container) {
                    $tableGateway = $container->get(Model\TeamTableGateway::class);
                    return new Model\TeamTable($tableGateway);
                },
                Model\TeamTableGateway::class => function ($container) {
                    $dbAdapter = $container->get('Db\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Model\Team());
                    return new TableGateway(BaseTable::TEAM_TABLE, $dbAdapter, null, $resultSetPrototype);
                },
                Model\InventoryTable::class => function ($container) {
                    $tableGateway = $container->get(Model\InventoryTableGateway::class);
                    return new Model\InventoryTable($tableGateway);
                },
                Model\InventoryTableGateway::class => function ($container) {
                    $dbAdapter = $container->get('Db\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Model\Inventory());
                    return new TableGateway(BaseTable::INVENTORY_TABLE, $dbAdapter, null, $resultSetPrototype);
                },
                Model\InventoryIncrementTable::class => function ($container) {
                    $tableGateway = $container->get(Model\InventoryIncrementableGateway::class);
                    return new Model\InventoryIncrementTable($tableGateway);
                },
                Model\InventoryIncrementTableGateway::class => function ($container) {
                    $dbAdapter = $container->get('Db\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Model\InventoryIncrement());
                    return new TableGateway(BaseTable::INVENTORY_INCREMENT_TABLE, $dbAdapter, null, $resultSetPrototype);
                },
            ]
        ];
    }

    public function getControllerConfig() {
        return [
            'factories' => [
                Controller\TeamController::class => function ($container) {
                    return new Controller\TeamController($container);
                },
                Controller\InventoryController::class => function ($container) {
                    return new Controller\InventoryController($container);
                }
            ]
        ];
    }
}