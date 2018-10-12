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
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Stdlib\ArrayUtils;

class PackageTable extends BaseTable {

    public function getPackageList(User $user = null, $packageType = Package::PROCESS_TYPE_IN, $keywords = []) {
        $where = new Where();
        $where->equalTo('type', $packageType);

        $select = new Select();
        $select->from(['pa' => $this->getTable()])
            ->join(['t' => BaseTable::TEAM_TABLE], 'pa.teamId = t.id', ['team' => 'name'], Select::JOIN_LEFT)
            ->join(['p' => BaseTable::PRODUCT_TABLE], 'pa.productId = p.id', ['itemName'], Select::JOIN_LEFT)
            ->join(['b' => BaseTable::BRAND_TABLE], 'p.brandId = b.id', ['brand' => 'name'], Select::JOIN_LEFT)
            ->order('processDate DESC');
        if ($packageType == Package::PROCESS_TYPE_OUT) {
            $select->join(['s' => BaseTable::SHIPPING_TABLE], 'pa.id = s.packageId', ['shippingCost', 'shippingFee', 'serviceFee', 'customs', 'carrier'], Select::JOIN_LEFT);
        }
        if (!empty($user) && !$user->isManager()) {
            $where->equalTo('pa.teamId', $user->teamId);
        } elseif ($packageType == Package::PROCESS_TYPE_OUT && $user->isManager()) {
            if (!empty($keywords['itemName'])) {
                $where->equalTo('p.itemName', $keywords['itemName']);
            }
            if (!empty($keywords['teamId'])) {
                $where->equalTo('pa.teamId', $keywords['teamId']);
            }
            if (!empty($keywords['carrier'])) {
                $where->equalTo('s.carrier', $keywords['carrier']);
            }
        }
        $select->where($where);
        return $this->tableGateway->selectWith($select);
    }

    public function getInvoice($packageIds) {
        $select = new Select();
        $select->from(['pa' => $this->getTable()])
            ->join(['t' => BaseTable::TEAM_TABLE], 'pa.teamId = t.id', ['team' => 'name'], Select::JOIN_LEFT)
            ->join(['p' => BaseTable::PRODUCT_TABLE], 'pa.productId = p.id', ['itemName'], Select::JOIN_LEFT)
            ->join(['b' => BaseTable::BRAND_TABLE], 'p.brandId = b.id', ['brand' => 'name'], Select::JOIN_LEFT)
            ->join(['s' => BaseTable::SHIPPING_TABLE], 'pa.id = s.packageId', ['shippingCost', 'shippingFee', 'serviceFee', 'customs', 'carrier', 'trackingNumber'], Select::JOIN_LEFT)
            ->join(['a' => BaseTable::ADDRESS_TABLE], 's.addressId = a.id', ['country', 'zip'], Select::JOIN_LEFT)
            ->order('processDate DESC');
        if (!empty($packageIds)) {
            $where = new Where();
            $where->in('pa.id', $packageIds);
            $select->where($where);
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

    public function getFees($params) {
        $select = $this->selectTable('p')
            ->join(['t' => BaseTable::TEAM_TABLE], 'p.teamId = t.id', ['team' => 'name'], Select::JOIN_LEFT)
            ->join(['s' => BaseTable::SHIPPING_TABLE], 'p.id = s.packageId', [
                'shippingCost' => new Expression('SUM(shippingCost)'),
                'shippingFee' => new Expression('SUM(shippingFee)'),
                'serviceFee' => new Expression('SUM(serviceFee)'),
                'customs' => new Expression('SUM(customs)')
            ], Select::JOIN_LEFT)
            ->group('p.teamId');
        $where = new Where();
        if (!empty($params['teamId'])) {
            $where->equalTo('p.teamId', $params['teamId']);
        }
        if (!empty($params['date'])) {
            $where->like('p.processDate', $params['date'] . '%');
        }
        $select->where($where);
        return $this->tableGateway->selectWith($select);
    }
}