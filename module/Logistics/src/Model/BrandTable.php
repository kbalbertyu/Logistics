<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/23/2018
 * Time: 8:33 AM
 */

namespace Logistics\Model;


use Application\Model\BaseTable;
use Zend\Db\Sql\Where;

class BrandTable extends BaseTable {

    public function getBrandId($name) {
        $name = trim($name);
        $set = ['name' => $name];
        $find = $this->getRowByFields($set);
        if ($find) {
            return $find->id;
        }
        if ($this->add($set)) {
            return $this->getInsertId();
        }
        throw new \RuntimeException('Unable to save brand by name: ' . $name);
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