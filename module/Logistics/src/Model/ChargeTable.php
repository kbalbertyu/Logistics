<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/26/2018
 * Time: 8:09 AM
 */

namespace Logistics\Model;


use Application\Model\BaseTable;
use Zend\Db\Sql\Select;

class ChargeTable extends BaseTable {

    public function getHistory() {
        $select = new Select();
        $select->from(['c' => $this->getTable()])
            ->join(['t' => BaseTable::TEAM_TABLE],
                'c.teamId = t.id', ['team' => 'name'], Select::JOIN_LEFT)
            ->join(['p' => BaseTable::PRODUCT_TABLE],
                'c.productId = p.id', ['itemName'], Select::JOIN_LEFT)
            ->order('date DESC');
        return $this->tableGateway->selectWith($select);
    }
}