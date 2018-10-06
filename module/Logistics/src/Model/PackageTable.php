<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/22/2018
 * Time: 8:27 AM
 */

namespace Logistics\Model;


use Application\Model\BaseTable;
use User\Model\User;
use Zend\Db\Sql\Select;
use Zend\Stdlib\ArrayUtils;

class PackageTable extends BaseTable {

    public function getPackageList(User $user = null, $packageType = Package::PROCESS_TYPE_IN) {
        $select = new Select();
        $select->from(['i' => $this->getTable()])
            ->join(['t' => BaseTable::TEAM_TABLE], 'i.teamId = t.id', ['team' => 'name'])
            ->join(['p' => BaseTable::PRODUCT_TABLE], 'i.productId = p.id', ['itemName'])
            ->join(['b' => BaseTable::BRAND_TABLE], 'p.brandId = b.id', ['brand' => 'name'])
            ->where(['type' => $packageType])
            ->order('processDate DESC');
        if (!empty($user) && !$user->isManager()) {
            $select->where(['i.teamId' => $user->teamId]);
        }
        return $this->tableGateway->selectWith($select);
    }

    public function savePackage($data, $isManager, $id = null) {
        $time = date('Y-m-d H:i:s');
        $set = [
            'productId' => $data['productId'],
            'qty' => $data['qty'],
            'caseQty' => $data['caseQty'],
            'note' => $data['note'],
            'recordDate' => $time,
        ];

        if ($isManager) {
            $set = ArrayUtils::merge($set, [
                'length' => $data['length'],
                'width' => $data['width'],
                'height' => $data['height'],
                'status' => $data['status'],
                'weight' => $data['weight']
            ]);
        }

        if (empty($id)) {
            $set = ArrayUtils::merge($set, [
                'teamId' => $data['teamId'],
                'type' => $data['type'],
                'processDate' => $time,
                'username' => $data['username']
            ]);
            if (!isset($set['status'])) {
                $set['status'] = 'pending';
            }
            $this->add($set);
            return $this->getInsertId();
        }
        $this->update($set, $id);
        return $id;
    }
}