<?php
namespace User\Controller;

use Logistics\Model\TeamTable;
use User\Model\UserTable;
use Zend\Stdlib\ArrayUtils;
use Zend\ServiceManager\ServiceLocatorInterface;
use Application\Controller\AbstractBaseController;
use User\Model\User;

/**
 * @property UserTable table
 */
class UserController extends AbstractBaseController {
    
    public function __construct(ServiceLocatorInterface $container) {
        parent::__construct($container);
        $this->table = $this->container->get(UserTable::class);
    }

    public function indexAction() {
        if (($view = $this->onlyManagers()) != null) {
            return $view;
        }
        $this->title = $this->__('nav.users');
        $this->nav = 'user';
        $this->addOutPut('users', $this->table->getUserList());
        return $this->renderView();
    }

    public function loginAction() {
        $this->title = $this->__('user.login');
        if ($this->user) {
            $this->redirect()->toRoute('inventory');
        }
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            
            $result = $this->table->auth($data);
            if (!$result->isValid()) {
                $message = $this->__('login.failed', ['username' => $data['username']]);
                $this->logger->warn($message);
                $this->flashMessenger()->addErrorMessage($message);
                $this->redirect()->toRoute('user');
                return;
            }
            
            $username = $result->getIdentity();
            $this->auth->getStorage()->write($username);
            
            $user = $this->table->getRowById($username);
            $route = 'user';

            if (ArrayUtils::inArray($user->role, User::DEFAULT_ROUTES)) {
                $route = User::DEFAULT_ROUTES[$user->role];
            }
            $this->redirect()->toRoute($route);
        }
        return $this->renderView();
    }
    
    public function logoutAction() {
        $this->auth->clearIdentity();
        $this->redirect()->toRoute('user');
    }
    
    public function editAction() {
        if (($view = $this->checkPermission()) != null) {
            return $view;
        }
        $this->title = $this->__('user.profile');
        $id = $this->params()->fromRoute('id');
        if (empty($id)) {
            $id = $this->user;
        }
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            if (!$this->userObject->isManager()) {
                unset($data['teamId']);
            }
            $this->table->edit($data, $id);
            $this->flashMessenger()->addSuccessMessage($this->__('user.updated'));
            $this->redirect()->refresh();
        }
        $this->addOutPut([
            'user' => $this->table->getRowById($id),
            'teams' => $this->getTableModel(TeamTable::class)->getRows()
        ]);
        return $this->renderView();
    }
    
    public function registerAction() {
        $this->title = $this->__('user.register');
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->table->register($data);
            $this->addOutPut([
                'isValid' => $result->isValid(),
                'message' => $result->getMessage()
            ]);
        }
        return $this->renderView();
    }
}