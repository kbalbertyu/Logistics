<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/22/2018
 * Time: 7:24 AM
 */

namespace Logistics\Model;


use Application\Model\BaseTable;

class TeamTable extends BaseTable {

    public function nameExists($name, $id = null) {
        $find = $this->getRowByFields(['name' => $name]);
        return !empty($find) && (empty($id) || $find->id != $id);
    }

    public function getTeamListForSelection() {
        $select = $this->selectTable()
            ->order('name');
        return $this->tableGateway->selectWith($select);
    }

    public function getNeedUpdateFeeTeamIds() {
        $where = $this->where()
            ->notEqualTo('feeUpdateDate', date('Y-m-d'))
            ->or
            ->isNull('feeUpdateDate');
        $select = $this->selectTable()
            ->where($where);
        $rows = $this->tableGateway->selectWith($select);
        return !$rows->count() ? [] :
            array_column($rows->toArray(), 'id');
    }

    public function updateStorageFees($storageFees) {
        foreach ($storageFees as $fee) {
            $this->update([
                'storageFee' => $fee['fee'],
                'feeUpdateDate' => date('Y-m-d')
            ], $fee['teamId']);
        }
    }
}