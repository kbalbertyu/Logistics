<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/22/2018
 * Time: 8:26 AM
 */

namespace Logistics\Model;


use Application\Model\BaseModel;
use Application\Model\Tools;
use Application\Model\Validation;

/**
 * @property int id
 * @property int productId
 * @property int teamId
 * @property int qty
 * @property int caseQty
 * @property string type
 * @property float length
 * @property float width
 * @property float height
 * @property float weight
 * @property string processDate
 * @property string recordDate
 * @property string status
 * @property string username
 * @property string note
 */
class Package extends BaseModel {

    public const NUMERIC_COLUMNS = ['qty', 'shippingCost', 'shippingFee', 'serviceFee', 'customs', 'length', 'width', 'height', 'weight'];

    public const PROCESS_TYPE_OUT = 'out';

    public const PROCESS_TYPE_IN = 'in';

    public const STATUS_LIST = [
        'pending' => [
            'label' => 'status.pending',
            'color' => 'danger'
        ],
        'in-process' => [
            'label' => 'status.in.process',
            'color' => 'warning'
        ],
        'shipped' => [
            'label' => 'status.shipped',
            'color' => 'primary'
        ],
        'completed' => [
            'label' => 'status.completed',
            'color' => 'success'
        ]
    ];

    public const SHIPPED_STATUS = ['shipped', 'completed'];

    private const REQUIRED_COLUMNS = ['itemName', 'brand', 'teamId', 'qty', 'type'];

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
            if ($field == 'qty' && !empty($value) && !is_numeric($value)) {
                $validation->addError(Tools::__('field.is.numeric', ['field' => $field]));
            }
        }
        return $validation;
    }

    public function renderSize() {
        return sprintf('%s=%.2f, %s=%.2f, %s=%.2f',
            $this->__('length'), $this->length,
            $this->__('width'), $this->width,
            $this->__('height'), $this->height);
    }

    public function renderProcessType() {
        return sprintf('<span class="badge badge-%s">%s</span>',
            $this->type == self::PROCESS_TYPE_IN ? 'success' : 'warning',
            $this->type == self::PROCESS_TYPE_IN ? Tools::__('receive') : Tools::__('ship.package'));
    }

    public function renderStatus() {
        return sprintf('<span class="badge badge-%s">%s</span>',
            self::STATUS_LIST[$this->status]['color'],
            Tools::__(self::STATUS_LIST[$this->status]['label']));
    }
}