<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/27/2018
 * Time: 1:13 AM
 */

namespace Logistics\Model;


class ShippingTable extends PackageTable {

    /**
     * Count the existences of the given addressId
     * @param $addressId
     * @return int
     */
    public function getAddressUseCount($addressId) {
        return $this->getCount(['addressId' => $addressId]);
    }

    public function saveShipping($data, $id = null) {
        $data = $this->getModel()->removeExtraColumns($data);
        $find = !empty($id) ? $this->getRowById($id) : null;
        if (empty($find)) {
            $this->add($data);
            $id = $this->getInsertId();
        } else {
            $this->update($data, $id);
        }
        return $id;
    }
}