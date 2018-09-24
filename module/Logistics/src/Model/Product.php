<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/22/2018
 * Time: 8:30 AM
 */

namespace Logistics\Model;


use Application\Model\BaseModel;

/**
 * @property int id
 * @property int teamId
 * @property string itemName
 * @property int brandId
 * @property float length
 * @property float width
 * @property float height
 * @property float weight
 */
class Product extends BaseModel {

    public const NUMERIC_COLUMNS = ['length', 'width', 'height', 'weight'];

    public function renderSize() {
        return sprintf('Length=%.2f, Width=%.2f, Height=%.2f',
            $this->length, $this->weight, $this->height);
    }
}