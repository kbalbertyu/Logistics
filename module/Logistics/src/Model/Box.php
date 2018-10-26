<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 10/24/2018
 * Time: 11:00 PM
 */

namespace Logistics\Model;


use Application\Model\BaseModel;

/**
 * @property int id
 * @property int productId
 * @property int inPackageId
 * @property int outPackageId
 * @property int qty
 * @property int qtyOut
 * @property float volume
 * @property float weight
 * @property string dateIn
 * @property string dateOut
 */
class Box extends BaseModel {

    public function shipped() {
        return !empty($this->outPackageId) && !empty($this->dateOut);
    }
}