<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/22/2018
 * Time: 8:32 AM
 */

namespace Logistics\Model;


use Application\Model\BaseModel;
use Application\Model\BaseTable;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Stdlib\ArrayUtils;

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
            return $this->getRowById($data['itemName'])->id;
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
        $set = ArrayUtils::merge($set, [
            'length' => $data['length'],
            'width' => $data['width'],
            'height' => $data['height'],
            'weight' => $data['weight']
        ]);
        BaseModel::filterNumericColumns($set, Product::NUMERIC_COLUMNS);
        if ($this->add($set)) {
            return $this->getInsertId();
        }
        throw new \RuntimeException(sprintf('Unable to save product: brand=%d, team=%d, item name=%s',
            $data['brandId'], $data['teamId'], $data['itemName']));
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
}