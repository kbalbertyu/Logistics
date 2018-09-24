<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/22/2018
 * Time: 7:22 AM
 */

namespace Logistics\Controller;


use Application\Controller\AbstractBaseController;
use Logistics\Model\TeamTable;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Model\ViewModel;

/**
 * @property TeamTable table
 */
class TeamController extends AbstractBaseController {

    public function __construct(ServiceLocatorInterface $container) {
        parent::__construct($container);
        $this->table = $this->getTableModel(TeamTable::class);
    }

    public function indexAction() {
        $this->title = 'Business Teams';
        $this->nav = 'team';
        $teams = $this->table->getRows();
        return new ViewModel([
            'teams' => $teams
        ]);
    }

    public function addAction() {
        $data = [
            'valid' => false,
            'message' => ''
        ];
        if ($this->getRequest()->isPost()) {
            $name = $this->getRequest()->getPost('name');
            $name = trim($name);
            if (empty($name)) {
                $data['message'] = 'Team name exists: ' . $name;
            } elseif($this->table->nameExists($name)) {
                $data['message'] = 'Team name exists: ' . $name;
            } else {
                $saved = $this->table->add([
                    'name' => $name
                ]);
                if ($saved) {
                    $data['message'] = 'Team name saved: ' . $name;
                    $this->flashMessenger()->addSuccessMessage($data['message']);
                    $this->redirect()->toRoute('team');
                } else {
                    $data['valid'] = true;
                }
            }
        }
        return new ViewModel($data);
    }
}