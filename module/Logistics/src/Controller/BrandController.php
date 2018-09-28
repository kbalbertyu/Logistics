<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/11/2018
 * Time: 3:05 AM
 */

namespace Logistics\Controller;


use Application\Controller\AbstractBaseController;
use Logistics\Model\BrandTable;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * @property BrandTable table
 */
class BrandController extends AbstractBaseController {

    public function __construct(ServiceLocatorInterface $container) {
        parent::__construct($container);
        $this->table = $this->getTableModel(BrandTable::class);
    }

    public function indexAction() {
        $this->title = $this->__('nav.brands');
        $this->nav = 'brand';
        $this->addOutPut('brands', $this->table->getRows());
        return $this->renderView();
    }
}