<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/11/2018
 * Time: 3:11 AM
 */

namespace Logistics\Controller;


use Application\Controller\AbstractBaseController;
use Zend\Mvc\MvcEvent;

class LogisticsBaseController extends AbstractBaseController {

    public function onDispatch(MvcEvent $e) {
        parent::onDispatch($e);
        $this->layout()->setTemplate('layout/logistics-layout');
    }
}