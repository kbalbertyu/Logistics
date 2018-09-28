<?php
namespace Application\Controller;

use Application\Model\Tools;
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

    /**
     * @var array output data rendered to view
     */
    protected $outPutData = [];

    public function __construct(ServiceLocatorInterface $container) {
        $this->logger = BaseModel::getLogger();
        $this->container = $container;
        $auth = new AuthenticationService();
        $auth->setStorage(new SessionStorage('userSession'));
        $this->auth = $auth;
        $this->user = $this->auth->getIdentity();
        $this->initUser();
    }

    protected function __(string $key, $parameters = []) {
        return Tools::__($key, $parameters);
    }

    protected function addOutPut($key, $value = null) {
        if (!is_array($key)) {
            $this->outPutData[$key] = $value;
            return;
        }
        foreach ($key as $k => $v) {
            $this->outPutData[$k] = $v;
        }
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
        try {
            $userTable = $this->getTableModel(UserTable::class);
            $this->userObject = $userTable->getRowById($this->user);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function onDispatch(MvcEvent $e) {
        parent::onDispatch($e);
        $this->checkLogin();

        $this->layout()->setVariables([
            'title' => $this->title,
            'nav' => $this->nav,
            'subNav' => $this->params()->fromRoute('action'),
            'user' => $this->user,
            't' => Tools::getTranslator()
        ]);
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

    protected function renderView() {
        $this->addOutPut('t', Tools::getTranslator());
        return new ViewModel($this->outPutData);
    }

    protected function listFiles($path) {
        return array_slice(scandir($path), 2);
    }

    private function checkLogin(): void {
        $action = $this->params()->fromRoute('action');
        if (!$this->user && $action !== 'login') {
            $this->redirect()->toRoute('user');
        }
    }
}

