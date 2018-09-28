<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/10/2018
 * Time: 10:24 PM
 */

namespace Logistics\Controller;


use Application\Controller\AbstractBaseController;

class IndexController extends AbstractBaseController {

    public function indexAction() {
        $this->addOutPut([
            'content' => 'Hello World'
        ]);
        return $this->renderView();
    }
}