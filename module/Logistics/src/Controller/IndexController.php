<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/10/2018
 * Time: 10:24 PM
 */

namespace Logistics\Controller;


use Zend\View\Model\ViewModel;

class IndexController extends LogisticsBaseController {

    public function indexAction() {
        return new ViewModel([
            'content' => 'Hello World'
        ]);
    }
}