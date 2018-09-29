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
 */
class Address extends BaseModel {

    public function equalsTo($data) {
        return $data['recipient'] == $this->recipient &&
            $data['phone'] = $this->phone &&
                $data['address'] == $this->address;
    }
}