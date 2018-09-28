<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/27/2018
 * Time: 1:24 AM
 */

namespace Logistics\Model;


use Application\Model\BaseTable;

class AddressTable extends BaseTable {

    /**
     * @param array $data
     * @param int $id
     * @param bool $replace Replace the current record or add new record
     * @return int
     * @throws \Exception
     */
    public function saveAddress($data, $id = 0, $replace = false) {
        if (!empty($id)) {
            $address = $this->getRowById($id);
        }
        $set = [
            'recipient' => $data['recipient'],
            'phone' => $data['phone'],
            'address' => $data['address']
        ];
        if (!empty($address)) {
            if ($address->equalsTo($data)) {
                return $id;
            } elseif ($replace) {
                $this->update($set, $id);
                return $id;
            }
        }
        $this->add($set);
        return $this->getInsertId();
    }
}