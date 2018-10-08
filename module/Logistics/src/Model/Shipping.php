<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/27/2018
 * Time: 1:13 AM
 */

namespace Logistics\Model;

use Application\Model\Tools;

/**
 * @property int packageId
 * @property int addressId
 * @property string productLabel
 * @property string trackingNumber
 * @property string carrier
 * @property string amazonSendCheckList
 * @property string amazonShippingLabel
 * @property string productImage
 * @property float shippingCost
 * @property float shippingFee
 * @property float serviceFee
 * @property float customs
 * @property string customsNumber
 * @property boolean needUnpack
 * @property boolean needClean
 * @property boolean needCoverLogo
 * @property boolean needBook
 * @property boolean needCaseFill
 * @property boolean needProductLabel
 * @property boolean needBoxChange
 * @property string dateUpdated
 */
class Shipping extends Package {

    private const SERVICE_FEE_PER_ITEM = 0.3;

    private const SERVICE_FEE_PER_BOX = 20;

    private const BOX_UNIT_COLUMN = 'needBoxChange';

    public const REQUIREMENT_COLUMNS = [
        'needUnpack' => 'need.unpack',
        'needClean' => 'need.clean',
        'needCoverLogo' => 'need.cover.logo',
        'needBook' => 'need.book',
        'needCaseFill' => 'need.case.fill',
        'needProductLabel' => 'need.product.label',
        'needBoxChange' => 'need.box.change'
    ];

    public const ATTACHMENTS = [
        'amazonSendCheckList' => 'amazon.send.check.list',
        'productLabel' => 'product.label.file',
        'amazonShippingLabel' => 'amazon.shipping.label',
        'productImage' => 'product.image',
    ];

    public static function deleteAttachment($file) {
        @unlink(ZF_PATH . '/' . Tools::ATTACHMENT_PATH . $file);
    }

    public static function calcServiceFee($data) {
        $total = 0;
        foreach (self::REQUIREMENT_COLUMNS as $column => $label) {
            if (empty($data[$column])) {
                continue;
            }
            if ($column == self::BOX_UNIT_COLUMN) {
                $total += $data['caseQty'] * self::SERVICE_FEE_PER_BOX;
            } else {
                $total += $data['qty'] * self::SERVICE_FEE_PER_ITEM;
            }
        }
        return $total;
    }

    public function getAttachment($name) {
        return Tools::ATTACHMENT_URL_PATH . $this->$name;
    }

    public function removeExtraColumns($data) {
        $data = parent::removeExtraColumns($data);
        foreach (self::REQUIREMENT_COLUMNS as $column => $label) {
            if (!isset($data[$column])) {
                $data[$column] = 0;
            }
        }
        return $data;
    }
}