<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/22/2018
 * Time: 7:22 AM
 */

namespace Logistics\Controller;


use Application\Controller\AbstractBaseController;
use Logistics\Model\ChargeTable;
use Logistics\Model\PackageTable;
use Logistics\Model\TeamTable;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * @property TeamTable table
 * @property PackageTable packageTable
 * @property ChargeTable chargeTable
 */
class TeamController extends AbstractBaseController {

    public function __construct(ServiceLocatorInterface $container) {
        parent::__construct($container);
        $this->table = $this->getTableModel(TeamTable::class);
        $this->packageTable = $this->getTableModel(PackageTable::class);
        $this->chargeTable = $this->getTableModel(ChargeTable::class);
    }

    public function indexAction() {
        if (($view = $this->onlyManagers()) != null) {
            return $view;
        }
        $this->title = $this->__('nav.teams');
        $this->nav = 'team';
        $this->addOutPut([
            'teams' => $this->table->getRows(),
            'fees' => $this->packageTable->getTeamFeeList(),
            'feesPaid' => $this->chargeTable->getTeamChargeList()
        ]);
        return $this->renderView();
    }

    public function addAction() {
        $this->title = $this->__('team.add');
        if ($this->getRequest()->isPost()) {
            $name = $this->getRequest()->getPost('name');
            $name = trim($name);
            if (empty($name)) {
                $this->flashMessenger()->addErrorMessage($this->__('team.name.empty'));
                $this->redirect()->refresh();
                return;
            } elseif($this->table->nameExists($name)) {
                $this->flashMessenger()->addErrorMessage($this->__('team.name.exists', ['name' => $name]));
                $this->redirect()->refresh();
                return;
            } else {
                $this->table->add(['name' => $name]);
                $this->flashMessenger()->addSuccessMessage($this->__('team.saved', ['name' => $name]));
                $this->redirect()->toRoute('team');
                return;
            }
        }
        return $this->renderView();
    }

    public function editAction() {
        $this->title = $this->__('team.edit');
        $id = $this->params()->fromRoute('id');
        if (empty($id)) {
            $this->flashMessenger()->addErrorMessage($this->__('invalid.parameter'));
            $this->redirect()->toRoute('team');
            return;
        }
        $team = $this->table->getRowById($id);
        if (empty($team)) {
            $this->flashMessenger()->addErrorMessage($this->__('team.id.invalid', ['id' => $id]));
            $this->redirect()->toRoute('team');
            return;
        }
        $this->addOutPut('team', $team);
        if ($this->getRequest()->isPost()) {
            $name = $this->getRequest()->getPost('name');
            $name = trim($name);
            if (empty($name)) {
                $this->flashMessenger()->addErrorMessage($this->__('team.name.empty'));
                $this->redirect()->refresh();
                return;
            } elseif($this->table->nameExists($name, $id)) {
                $this->flashMessenger()->addErrorMessage($this->__('team.name.exists', ['name' => $name]));
                $this->redirect()->refresh();
                return;
            } else {
                $this->table->update(['name' => $name], $id);
                $this->flashMessenger()->addSuccessMessage($this->__('team.saved', ['name' => $name]));
                $this->redirect()->refresh();
                return;
            }
        }
        return $this->renderView();
    }
}