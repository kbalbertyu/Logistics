<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/22/2018
 * Time: 7:22 AM
 */

namespace Logistics\Controller;


use Application\Controller\AbstractBaseController;
use Logistics\Model\ProductTable;
use Logistics\Model\TeamTable;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * @property TeamTable table
 * @property ProductTable productTable
 */
class TeamController extends AbstractBaseController {

    public function __construct(ServiceLocatorInterface $container) {
        parent::__construct($container);
        $this->table = $this->getTableModel(TeamTable::class);
        $this->productTable = $this->getTableModel(ProductTable::class);
    }

    public function indexAction() {
        if (($view = $this->onlyManagers()) != null) {
            return $view;
        }
        $this->title = $this->__('nav.teams');
        $this->nav = 'team';
        $this->addOutPut([
            'teams' => $this->table->getRows(),
            'fees' => $this->productTable->getTeamFeesDueList()
        ]);
        return $this->renderView();
    }

    public function addAction() {
        $this->title = $this->__('team.add');
        $data = [
            'valid' => false,
            'message' => ''
        ];
        if ($this->getRequest()->isPost()) {
            $name = $this->getRequest()->getPost('name');
            $name = trim($name);
            if (empty($name)) {
                $data['message'] = $this->__('team.name.empty');
            } elseif($this->table->nameExists($name)) {
                $data['message'] = $this->__('team.name.exists', ['name' => $name]);
            } else {
                $saved = $this->table->add([
                    'name' => $name
                ]);
                if ($saved) {
                    $data['message'] = $this->__('team.saved', ['name' => $name]);
                    $this->flashMessenger()->addSuccessMessage($data['message']);
                    $this->redirect()->toRoute('team');
                } else {
                    $data['valid'] = true;
                }
            }
        }
        $this->addOutPut($data);
        return $this->renderView();
    }
}