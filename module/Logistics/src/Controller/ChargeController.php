<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/26/2018
 * Time: 9:10 AM
 */

namespace Logistics\Controller;


use Application\Controller\AbstractBaseController;
use Logistics\Model\ChargeTable;
use Logistics\Model\PackageTable;
use Logistics\Model\TeamTable;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * @property ChargeTable table
 * @property PackageTable packageTable
 * @property TeamTable teamTable
 */
class ChargeController extends AbstractBaseController {

    public function __construct(ServiceLocatorInterface $container) {
        parent::__construct($container);
        $this->table = $this->getTableModel(ChargeTable::class);
        $this->packageTable = $this->getTableModel(PackageTable::class);
        $this->teamTable = $this->getTableModel(TeamTable::class);
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
}