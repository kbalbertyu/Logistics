<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;

class IndexController extends AbstractActionController {
    
    public function indexAction() {
        return new ViewModel();
    }
    
    public function accessDeniedAction() {
        return new ViewModel();
    }
    
    public function accessDeniedAjaxAction() {
        $this->useJsonLayout();
        return new ViewModel([
            'data' => Json::encode([
                'code' => 0,
                'message' => 'Access denied'
            ])
        ]);
    }
}
