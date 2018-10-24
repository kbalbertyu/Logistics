<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/22/2018
 * Time: 8:30 AM
 */

namespace Logistics\Model;


use Application\Model\BaseModel;
use Application\Model\Tools;
use Application\Model\Validation;

/**
 * @property int id
 * @property int teamId
 * @property string itemName
 * @property int brandId
 * @property int qty
 * @property float shippingCost
 * @property float shippingFee
 * @property float serviceFee
 * @property float customs
 */
class Product extends BaseModel {
    private const REQUIRED_COLUMNS = ['itemName', 'brand'];

    /**
     * @param $data
     * @return Validation
     */
    public static function validate($data) {
        $validation = new Validation();
        foreach ($data as $field => $value) {
            if (in_array($field, self::REQUIRED_COLUMNS) && empty($value)) {
                $validation->addError(Tools::__('field.required', ['field' => $field]));
            }
        }
        return $validation;
    }
}