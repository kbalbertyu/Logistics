<?php
namespace User\Controller;

use Logistics\Model\TeamTable;
use User\Model\UserTable;
use Zend\Stdlib\ArrayUtils;
use Zend\View\Model\ViewModel;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Mvc\MvcEvent;
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
    
    public function onDispatch(MvcEvent $e) {
        parent::onDispatch($e);
        $this->layout()->setVariables([
            'title' => $this->title,
            'nav' => $this->params()->fromRoute('action'),
            'user' => $this->user
        ]);
    }

    public function indexAction() {
        $this->title = 'User List';
        $this->nav = 'user';
        $users = $this->table->getUserList();
        return new ViewModel([
            'users' => $users
        ]);
    }

    public function loginAction() {
        $this->title = 'User Login';
        if ($this->user) {
            $this->redirect()->toRoute('inventory');
        }
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            
            $result = $this->table->auth($data);
            if (!$result->isValid()) {
                $message = sprintf('Login failed: %s', $data['username']);
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
        return new ViewModel();
    }
    
    public function logoutAction() {
        $this->auth->clearIdentity();
        $this->redirect()->toRoute('user');
    }
    
    public function editAction() {
        if (($view = $this->checkPermission()) != null) {
            return $view;
        }
        $this->title = 'User Profile';
        $id = $this->params()->fromRoute('id');
        if (empty($id)) {
            $id = $this->user;
        }
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $this->table->edit($data, $id);
            $this->flashMessenger()->addSuccessMessage('User updated.');
            $this->redirect()->refresh();
        }
        return new ViewModel([
            'user' => $this->table->getRowById($id),
            'teams' => $this->getTableModel(TeamTable::class)->getRows()
        ]);
    }
    
    public function registerAction() {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->table->register($data);
            return new ViewModel([
                'isValid' => $result->isValid(),
                'message' => $result->getMessage()
            ]);
        }
    }
}