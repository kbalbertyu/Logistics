<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/27/2018
 * Time: 1:23 AM
 */

namespace Logistics\Model;


use Application\Model\BaseModel;

/**
 * @property int id
 * @property int teamId
 * @property string recipient
 * @property string phone
 * @property string address
 * @property string zip
 * @property string country
 */
class Address extends BaseModel {

    public static function isValid(&$data) {
        foreach (['recipient', 'phone', 'address'] as $column) {
            $data[$column] = trim($data[$column]);
            if (empty($data[$column])) {
                return false;
            }
        }
        return true;
    }

    public function equalsTo($data) {
        return $data['recipient'] == $this->recipient &&
            $data['phone'] = $this->phone &&
            $data['address'] == $this->address &&
            $data['zip'] == $this->zip &&
            $data['country'] == $this->country;
    }
}