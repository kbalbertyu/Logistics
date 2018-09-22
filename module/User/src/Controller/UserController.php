<?php
namespace User\Controller;

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
    
    public function loginAction() {
        $this->title = 'User Login';
        if ($this->user) {
            $this->redirect()->toRoute('user');
        }
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            
            $result = $this->table->auth($data);
            if (!$result->isValid()) {
                $this->logger->warn(sprintf('Login failed: %s', $data['username']));
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
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->table->edit($data, $this->user);
            return new ViewModel([
                'isValid' => $result->isValid(),
                'message' => $result->getMessage()
            ]);
        }
        return new ViewModel([
            'loginUser' => $this->user
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