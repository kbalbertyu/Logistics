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
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;

class ProductTable extends BaseTable {

    public function getProducts() {
        $select = new Select();
        $select->from(['p' => $this->getTable()])
            ->join(['t' => BaseTable::TEAM_TABLE], 'p.teamId = t.id', ['team' => 'name'])
            ->join(['b' => BaseTable::BRAND_TABLE], 'p.brandId = b.id', ['brand' => 'name'])
            ->order('itemName');
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
            foreach (['shippingCost', 'shippingFee', 'serviceFee'] as $column) {
                $set[$column] = new Expression(sprintf('%s + %.2f', $column, $data[$column]));
            }
            $set['feesDue'] = new Expression(sprintf('feesDue + %.2f',
                ($data['shippingFee'] + $data['serviceFee'] + $data['customs'])));
        } else {
            $sign = $package->type == Package::PROCESS_TYPE_IN ? 1 : -1;
            $set['qty'] =  $product->qty + $sign * ($data['qty'] - $package->qty);

            if ($this->withoutFees($data)) {
                return;
            }
            foreach (['shippingCost', 'shippingFee', 'serviceFee'] as $column) {
                $set[$column] = $product->$column + ($data[$column] - $shipping->$column);
            }
            $set['feesDue'] = $product->feesDue +
                ($data['shippingFee'] + $data['serviceFee'] + $data['customs']) -
                ($shipping->shippingFee + $shipping->serviceFee + $shipping->customs);
        }

        return $this->update($set, $data['productId']);
    }

    public function getTeamFeesDueList() {
        $select = $this->selectTable()
            ->columns(['teamId', 'feesDue' => new Expression('SUM(feesDue)')])
            ->group('teamId');
        $rows = $this->tableGateway->selectWith($select);
        if (!$rows->count()) {
            return [];
        }
        return array_column($rows->toArray(), 'feesDue', 'teamId');
    }

    /**
     * @param $data
     * @return bool
     */
    private function withoutFees($data) {
        return !isset($data['shippingCost']) || !isset($data['shippingFee']) || !isset($data['serviceFee']);
    }
}