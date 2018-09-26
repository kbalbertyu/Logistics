<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/26/2018
 * Time: 9:10 AM
 */

namespace Logistics\Controller;


use Application\Controller\AbstractBaseController;
use Logistics\Model\ChargeTable;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * @property ChargeTable table
 */
class ChargeController extends AbstractBaseController {

    public function __construct(ServiceLocatorInterface $container) {
        parent::__construct($container);
        $this->table = $this->getTableModel(ChargeTable::class);
        $this->nav = 'charge';
    }

    public function indexAction() {
        $this->title = 'Fee Charges History';
        $this->addOutPut('charges', $this->table->getHistory());
        return $this->renderView();
    }
}