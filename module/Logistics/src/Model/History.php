<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/22/2018
 * Time: 8:26 AM
 */

namespace Logistics\Model;


use Application\Model\BaseModel;
use Application\Model\Validation;

/**
 * @property int id
 * @property int inventoryId
 * @property int teamId
 * @property string qty
 * @property string type
 * @property float shippingCost
 * @property float shippingFee
 * @property float serviceFee
 * @property string processDate
 * @property string recordDate
 * @property string username
 * @property string note
 */
class History extends BaseModel {

    public const NUMERIC_COLUMNS = ['qty', 'shippingCost', 'shippingFee', 'serviceFee'];

    private const REQUIRED_COLUMNS = ['itemName', 'brand', 'teamId', 'qty', 'type'];

    /**
     * @param $data
     * @return Validation
     */
    public static function validate($data) {
        $validation = new Validation();
        foreach ($data as $field => $value) {
            if (in_array($field, self::REQUIRED_COLUMNS) && empty($value)) {
                $validation->addError($field . ' cannot be empty.');
            }
            if ($field == 'qty' && !empty($value) && !is_numeric($value)) {
                $validation->addError($field . ' should be numeric.');
            }
        }
        return $validation;
    }

    public function renderProcessType() {
        return sprintf('<span class="btn btn-%s">%s</span>',
            $this->type == 'in' ? 'success' : 'warning',
            $this->type == 'in' ? 'Received' : 'Sent Out');
    }
}