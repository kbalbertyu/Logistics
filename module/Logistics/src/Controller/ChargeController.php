<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/26/2018
 * Time: 9:10 AM
 */

namespace Logistics\Controller;


use Application\Controller\AbstractBaseController;
use Logistics\Model\BoxTable;
use Logistics\Model\ChargeTable;
use Logistics\Model\PackageTable;
use Logistics\Model\ProductTable;
use Logistics\Model\TeamTable;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * @property ChargeTable table
 * @property PackageTable packageTable
 * @property TeamTable teamTable
 * @property BoxTable boxTable
 * @property ProductTable productTable
 */
class ChargeController extends AbstractBaseController {

    public function __construct(ServiceLocatorInterface $container) {
        parent::__construct($container);
        $this->table = $this->getTableModel(ChargeTable::class);
        $this->packageTable = $this->getTableModel(PackageTable::class);
        $this->teamTable = $this->getTableModel(TeamTable::class);
        $this->boxTable = $this->getTableModel(BoxTable::class);
        $this->productTable = $this->getTableModel(ProductTable::class);
        $this->nav = 'charge';
    }

    public function indexAction() {
        if (($view = $this->onlyManagers()) != null) {
            return $view;
        }
        $this->title = $this->__('fee.charge.list');
        $this->addOutPut('charges', $this->table->getHistory());
        return $this->renderView();
    }

    public function feesAction() {
        if (($view = $this->onlyManagers()) != null) {
            return $view;
        }
        $this->title = $this->__('nav.fee.summary');
        $params = $this->params()->fromQuery();
        $this->addOutPut([
            'teamId' => $params['teamId'],
            'date' => $params['date'],
            'fees' => $this->packageTable->getFees($params),
            'teams' => $this->teamTable->getTeamListForSelection(),
        ]);
        return $this->renderView();
    }

    public function storageAction() {
        if (($view = $this->onlyManagers()) != null) {
            return $view;
        }
        $this->title = $this->__('nav.fee.storage');
        $params = $this->params()->fromQuery();

        $shippedPackages = $this->packageTable->getShippedPackages($params['teamId']);
        $packageIds = !$shippedPackages->count() ?
            [] : array_column($shippedPackages->toArray(), 'id');
        $boxes = $this->boxTable->getByShippedPackage($packageIds, $params['date']);

        $this->addOutPut([
            'teamId' => $params['teamId'],
            'date' => $params['date'],
            'boxes' => $boxes,
            'fees' => $this->packageTable->getFees($params),
            'teams' => $this->teamTable->getTeamListForSelection(),
        ]);
        return $this->renderView();
    }
}