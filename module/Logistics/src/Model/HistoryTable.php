<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/22/2018
 * Time: 8:27 AM
 */

namespace Logistics\Model;


use Application\Model\BaseModel;
use Application\Model\BaseTable;
use Zend\Db\Sql\Select;

class HistoryTable extends BaseTable {

    public function getInventoryList() {
        $select = new Select();
        $select->from(['i' => $this->getTable()])
            ->join(['t' => BaseTable::TEAM_TABLE], 'i.teamId = t.id', ['team' => 'name'])
            ->join(['p' => BaseTable::PRODUCT_TABLE], 'i.productId = p.id', ['itemName'])
            ->join(['b' => BaseTable::BRAND_TABLE], 'p.brandId = b.id', ['brand' => 'name'])
            ->order('processDate DESC');
        return $this->tableGateway->selectWith($select);
    }

    public function saveInventory($data) {
        $time = date('Y-m-d H:i:s');
        $set = [
            'productId' => $data['productId'],
            'teamId' => $data['teamId'],
            'qty' => $data['qty'],
            'type' => $data['type'],
            'shippingCost' => $data['shippingCost'],
            'shippingFee' => $data['shippingFee'],
            'serviceFee' => $data['serviceFee'],
            'processDate' => $time,
            'recordDate' => $time,
            'username' => $data['username'],
            'note' => $data['note']
        ];
        BaseModel::filterNumericColumns($set, History::NUMERIC_COLUMNS);
        return $this->add($set);
    }
}