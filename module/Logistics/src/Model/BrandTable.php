<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/23/2018
 * Time: 8:33 AM
 */

namespace Logistics\Model;


use Application\Model\BaseTable;
use RuntimeException;
use Zend\Db\Sql\Where;

class BrandTable extends BaseTable {

    public function getBrandId($name) {
        $name = trim($name);
        if (is_numeric($name)) {
            $find = $this->getRowById($name);
            if (empty($find)) {
                throw new RuntimeException('Brand ID invalid: ' . $name);
            }
            return $find->id;
        }
        $set = ['name' => $name];
        $find = $this->getRowByFields($set);
        if (!empty($find)) {
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
            $where->like('name', '%' . $keywords[$i] . '%');
        }
        $select = $this->selectTable()
            ->columns(['id', 'name'])
            ->where($where);
        $rows = $this->tableGateway->selectWith($select)->toArray();
        return empty($rows) ? [] : array_column($rows, 'name', 'id');
    }
}