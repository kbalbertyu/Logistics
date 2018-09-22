<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/11/2018
 * Time: 3:04 AM
 */

namespace Logistics\Controller;


use Zend\View\Model\ViewModel;

class InventoryController extends LogisticsBaseController {

    public function indexAction() {
        return new ViewModel();
    }
}