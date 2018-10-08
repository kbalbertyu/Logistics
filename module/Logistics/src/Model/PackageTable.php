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
        $select->from(['pa' => $this->getTable()])
            ->join(['t' => BaseTable::TEAM_TABLE], 'pa.teamId = t.id', ['team' => 'name'], Select::JOIN_LEFT)
            ->join(['p' => BaseTable::PRODUCT_TABLE], 'pa.productId = p.id', ['itemName'], Select::JOIN_LEFT)
            ->join(['b' => BaseTable::BRAND_TABLE], 'p.brandId = b.id', ['brand' => 'name'], Select::JOIN_LEFT)
            ->where(['type' => $packageType])
            ->order('processDate DESC');
        if ($packageType == Package::PROCESS_TYPE_OUT) {
            $select->join(['s' => BaseTable::SHIPPING_TABLE], 'pa.id = s.packageId', ['shippingCost', 'shippingFee', 'serviceFee', 'customs'], Select::JOIN_LEFT);
        }
        if (!empty($user) && !$user->isManager()) {
            $select->where(['pa.teamId' => $user->teamId]);
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
                'weight' => $data['weight']
            ]);
            if (isset($data['status'])) {
                $set['status'] = $data['status'];
            }
        }

        if (empty($id)) {
            $set = ArrayUtils::merge($set, [
                'teamId' => $data['teamId'],
                'type' => $data['type'],
                'processDate' => $time,
                'username' => $data['username']
            ]);
            if (!isset($set['status'])) {
                $set['status'] = $data['type'] == Package::PROCESS_TYPE_IN ? 'completed' : 'pending';
            }
            $this->add($set);
            return $this->getInsertId();
        }
        $this->update($set, $id);
        return $id;
    }
}