<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/22/2018
 * Time: 8:27 AM
 */

namespace Logistics\Model;


use Application\Model\BaseTable;
use Zend\Db\Sql\Select;
use Zend\Stdlib\ArrayUtils;

class PackageTable extends BaseTable {

    public function getPackageList() {
        $select = new Select();
        $select->from(['i' => $this->getTable()])
            ->join(['t' => BaseTable::TEAM_TABLE], 'i.teamId = t.id', ['team' => 'name'])
            ->join(['p' => BaseTable::PRODUCT_TABLE], 'i.productId = p.id', ['itemName'])
            ->join(['b' => BaseTable::BRAND_TABLE], 'p.brandId = b.id', ['brand' => 'name'])
            ->order('processDate DESC');
        return $this->tableGateway->selectWith($select);
    }

    public function savePackage($data, $id = null) {
        $time = date('Y-m-d H:i:s');
        $set = [
            'productId' => $data['productId'],
            'qty' => $data['qty'],
            'length' => $data['length'],
            'width' => $data['width'],
            'height' => $data['height'],
            'weight' => $data['weight'],
            'note' => $data['note'],
            'status' => $data['status'],
            'recordDate' => $time,
        ];

        if (empty($id)) {
            $set = ArrayUtils::merge($set, [
                'teamId' => $data['teamId'],
                'type' => $data['type'],
                'processDate' => $time,
                'username' => $data['username']
            ]);
            $this->add($set);
            return $this->getInsertId();
        }
        $this->update($set, $id);
        return $id;
    }
}