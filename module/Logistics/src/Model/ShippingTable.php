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

    /**
     * @param $data
     * @param null $id
     * @return Shipping
     * @throws \Exception
     */
    public function saveShipping($data, $id = null) {
        $shipping = new Shipping($data);
        $find = !empty($id) ? $this->getRowById($id) : null;
        if (empty($find)) {
            $this->add($shipping->toArray());
            $id = $this->getInsertId();
        } else {
            $this->update($shipping->toArray(), $id);
        }
        $shipping->id = $id;
        return $shipping;
    }
}