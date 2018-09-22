<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/10/2018
 * Time: 10:20 PM
 */

namespace Logistics;


use Zend\ModuleManager\Feature\ConfigProviderInterface;

class Module implements ConfigProviderInterface {

    public function getConfig() {
        return include __DIR__ . '/../config/module.config.php';
    }

    public function getServiceConfig() {
        return [
            'factories' => [
                /*Model\FBGroupTable::class => function ($container) {
                    $tableGateway = $container->get(Model\FBGroupTableGateway::class);
                    return new Model\FBGroupTable($tableGateway);
                },
                Model\FBGroupTableGateway::class => function ($container) {
                    $dbAdapter = $container->get('Db\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Model\FBGroup());
                    return new TableGateway(BaseTable::FB_GROUP_TABLE, $dbAdapter, null, $resultSetPrototype);
                },*/
            ]
        ];
    }

    public function getControllerConfig() {
        return [
            'factories' => [
                Controller\IndexController::class => function ($container) {
                    return new Controller\IndexController($container);
                }
            ]
        ];
    }
}