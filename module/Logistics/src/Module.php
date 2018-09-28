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
                Model\PackageTable::class => function ($container) {
                    $tableGateway = $container->get(Model\PackageTableGateway::class);
                    return new Model\PackageTable($tableGateway);
                },
                Model\PackageTableGateway::class => function ($container) {
                    $dbAdapter = $container->get('Db\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Model\Package());
                    return new TableGateway(BaseTable::PACKAGE_TABLE, $dbAdapter, null, $resultSetPrototype);
                },
                Model\ProductTable::class => function ($container) {
                    $tableGateway = $container->get(Model\ProductTableGateway::class);
                    return new Model\ProductTable($tableGateway);
                },
                Model\ProductTableGateway::class => function ($container) {
                    $dbAdapter = $container->get('Db\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Model\Product());
                    return new TableGateway(BaseTable::PRODUCT_TABLE, $dbAdapter, null, $resultSetPrototype);
                },
                Model\BrandTable::class => function ($container) {
                    $tableGateway = $container->get(Model\BrandTableGateway::class);
                    return new Model\BrandTable($tableGateway);
                },
                Model\BrandTableGateway::class => function ($container) {
                    $dbAdapter = $container->get('Db\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Model\Brand());
                    return new TableGateway(BaseTable::BRAND_TABLE, $dbAdapter, null, $resultSetPrototype);
                },
                Model\ChargeTable::class => function ($container) {
                    $tableGateway = $container->get(Model\ChargeTableGateway::class);
                    return new Model\ChargeTable($tableGateway);
                },
                Model\ChargeTableGateway::class => function ($container) {
                    $dbAdapter = $container->get('Db\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Model\Charge());
                    return new TableGateway(BaseTable::CHARGE_TABLE, $dbAdapter, null, $resultSetPrototype);
                },
                Model\ShippingTable::class => function ($container) {
                    $tableGateway = $container->get(Model\ShippingTableGateway::class);
                    return new Model\ShippingTable($tableGateway);
                },
                Model\ShippingTableGateway::class => function ($container) {
                    $dbAdapter = $container->get('Db\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Model\Shipping());
                    return new TableGateway(BaseTable::SHIPPING_TABLE, $dbAdapter, null, $resultSetPrototype);
                },
                Model\AddressTable::class => function ($container) {
                    $tableGateway = $container->get(Model\AddressTableGateway::class);
                    return new Model\AddressTable($tableGateway);
                },
                Model\AddressTableGateway::class => function ($container) {
                    $dbAdapter = $container->get('Db\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Model\Address());
                    return new TableGateway(BaseTable::ADDRESS_TABLE, $dbAdapter, null, $resultSetPrototype);
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
                },
                Controller\BrandController::class => function ($container) {
                    return new Controller\BrandController($container);
                },
                Controller\ChargeController::class => function ($container) {
                    return new Controller\ChargeController($container);
                }
            ]
        ];
    }
}