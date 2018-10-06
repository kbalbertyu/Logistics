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

    private const BOOLEAN_COLUMNS = ['needUnpack', 'needClean', 'needCoverLogo', 'needBook', 'needCaseFill', 'needProductLabel', 'needBoxChange'];

    public const ATTACHMENTS = [
        'amazonSendCheckList' => 'amazon.send.check.list',
        'productLabel' => 'product.label.file',
        'amazonShippingLabel' => 'amazon.shipping.label',
        'productImage' => 'product.image',
    ];

    public static function deleteAttachment($file) {
        @unlink(ZF_PATH . '/' . Tools::ATTACHMENT_PATH . $file);
    }

    public function getAttachment($name) {
        return Tools::ATTACHMENT_URL_PATH . $this->$name;
    }

    public function removeExtraColumns($data) {
        $data = parent::removeExtraColumns($data);
        foreach (self::BOOLEAN_COLUMNS as $column) {
            if (!isset($data[$column])) {
                $data[$column] = 0;
            }
        }
        return $data;
    }
}