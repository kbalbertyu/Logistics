<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 8/5/2018
 * Time: 11:11 PM
 */

namespace Setting;

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
                Model\SettingTable::class => function ($container) {
                    $tableGateway = $container->get(Model\SettingTableGateway::class);
                    return new Model\SettingTable($tableGateway);
                },
                Model\SettingTableGateway::class => function ($container) {
                    $dbAdapter = $container->get('Db\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Model\Setting());
                    return new TableGateway(BaseTable::SETTING_TABLE, $dbAdapter, null, $resultSetPrototype);
                }
            ]
        ];
    }

    public function getControllerConfig() {
        return [
            'factories' => [
            ]
        ];
    }
}