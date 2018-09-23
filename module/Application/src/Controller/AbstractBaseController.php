<?php
namespace Application\Controller;

use RuntimeException;
use Zend\Json\Json;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Authentication\AuthenticationService;
use Zend\Mvc\MvcEvent;
use Zend\Authentication\Storage\Session as SessionStorage;
use User\Model\UserTable;
use Zend\Log\Logger;
use Application\Model\BaseModel;
use Application\Model\BaseTable;
use Zend\View\Model\ViewModel;
use Zend\Console\Adapter\AdapterInterface as Console;

/**
 *
 * @author ACC22-8
 *
 */
abstract class AbstractBaseController extends AbstractActionController {

    protected $container;
    protected $user;
    protected $userObject;
    protected $title;

    /**
     *
     * @var Logger
     */
    protected $logger;

    /**
     * @var AuthenticationService
     */
    protected $auth;

    public function __construct(ServiceLocatorInterface $container) {
        $this->logger = BaseModel::getLogger();
        $this->container = $container;
        $auth = new AuthenticationService();
        $auth->setStorage(new SessionStorage('userSession'));
        $this->auth = $auth;
        $this->user = $this->auth->getIdentity();
        $this->initUser();
    }

    protected function initConsoleMode() {
        $console = $this->container->get('console');
        if (!$console instanceof Console) {
            throw new RuntimeException('Cannot obtain console adapter. Are we running in a console?');
        }
        $this->logger->setConsole($console);
    }

    protected function disableLogAndVariableDump() {
        defined('NO_VARIABLE_DUMP') || define('NO_VARIABLE_DUMP', true);
        defined('NO_LOG') || define('NO_LOG', true);
    }

    private function initUser() {
        if (!$this->user) {
            return;
        }
        $userTable = $this->container->get(UserTable::class);
        $this->userObject = $userTable->getRowById($this->user);
    }

    public function onDispatch(MvcEvent $e) {
        parent::onDispatch($e);
        $controller = $this->params()->fromRoute('controller');
        if (!$this->user && $controller == 'User\Controller\UserController') {
            $this->redirect()->toRoute('user');
        }
    }

    protected function checkPermission() {
        $auth = new \Application\Model\Auth();
        if ($auth->isAllow($this, $this->userObject)) {
            return null;
        } elseif (empty($this->userObject)) {
            $this->redirect()->toRoute('user');
        } else {
            $action = $this->getRequest()->isXmlHttpRequest() ? 'access-denied-ajax' : 'access-denied';
            return $this->forward()->dispatch('Application\Controller\IndexController', ['action' => $action]);
        }
    }

    /**
     *
     * @return BaseTable
     */
    protected function getTableModel($name) {
        return $this->container->get($name);
    }

    protected function useJsonLayout(): void {
        $this->layout()->setTemplate('layout/json');
    }

    protected function useBlankLayout(): void {
        $this->layout()->setTemplate('layout/blank');
    }

    protected function renderJsonView($data) {
        return new ViewModel([
            'data' => Json::encode($data)
        ]);
    }

    protected function listFiles($path) {
        return array_slice(scandir($path), 2);
    }
}

