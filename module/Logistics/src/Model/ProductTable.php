<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/22/2018
 * Time: 8:32 AM
 */

namespace Logistics\Model;


use Application\Model\BaseTable;
use Application\Model\Tools;
use RuntimeException;
use User\Model\User;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;

class ProductTable extends BaseTable {

    public function getProducts(User $user, $teamId = null, $itemName = null) {
        $where = new Where();
        if (!empty($user->username) && !$user->isManager()) {
            $where->equalTo('teamId', $user->teamId);
        } elseif ($user->isManager() && !empty($teamId)) {
            $where->equalTo('teamId', $teamId);
        }
        if (!empty($itemName)) {
            $where->like('itemName', '%' . $itemName . '%');
        }
        $select = $this->selectTable('p')
            ->join(['t' => BaseTable::TEAM_TABLE], 'p.teamId = t.id', ['team' => 'name'])
            ->join(['b' => BaseTable::BRAND_TABLE], 'p.brandId = b.id', ['brand' => 'name'])
            ->order('itemName')
            ->where($where);

        return $this->tableGateway->selectWith($select);
    }

    public function getProductId($data) {
        if (is_numeric($data['itemName'])) {
            $find = $this->getRowById($data['itemName']);
            if (empty($find)) {
                throw new RuntimeException(Tools::__('product.id.invalid', ['id' => $data['itemName']]));
            }
            return $find->id;
        }
        $set = [
            'itemName' => $data['itemName'],
            'brandId' => $data['brandId'],
            'teamId' => $data['teamId']
        ];
        $find = $this->getRowByFields($set, ['id']);
        if ($find) {
            return $find->id;
        }
        $this->add($set);
        return $this->getInsertId();
    }

    public function search($term) {
        $where = new Where();
        $keywords = explode(' ', $term);
        $count = count($keywords);
        for ($i = 0; $i < $count; $i++) {
            $where->like('itemName', '%' . $keywords[$i] . '%');
        }
        $select = $this->selectTable()
            ->columns(['id', 'itemName', 'brandId'])
            ->where($where);
        $rows = $this->tableGateway->selectWith($select);
        if (!$rows->count()) {
            return [];
        }
        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'label' => $row->itemName,
                'value' => $row->id,
                'brandId' => $row->brandId
            ];
        }
        return $data;
    }

    public function updateQtyAndFees($data, Package $package = null, Product $product = null, Shipping $shipping = null) {
        $set = [];
        if (empty($package)) {
            $set['qty'] = new Expression(sprintf('qty %s %d',
                $data['type'] == Package::PROCESS_TYPE_IN ? '+' : '-', $data['qty']));

            if ($this->withoutFees($data)) {
                return;
            }
            foreach (['shippingCost', 'shippingFee', 'serviceFee', 'customs'] as $column) {
                $set[$column] = new Expression(sprintf('%s + %.2f', $column, $data[$column]));
            }
        } else {
            $sign = $package->type == Package::PROCESS_TYPE_IN ? 1 : -1;
            $set['qty'] =  $product->qty + $sign * ($data['qty'] - $package->qty);

            if ($this->withoutFees($data)) {
                return;
            }
            if (empty($shipping)) {
                $shipping = new Shipping();
            }
            foreach (['shippingCost', 'shippingFee', 'serviceFee', 'customs'] as $column) {
                $set[$column] = $product->$column + ($data[$column] - $shipping->$column);
            }
        }

        return $this->update($set, $data['productId']);
    }

    /**
     * @param $data
     * @return bool
     */
    private function withoutFees($data) {
        return !isset($data['shippingCost']) || !isset($data['shippingFee']) ||
            !isset($data['serviceFee']) || !isset($data['customs']);
    }
}