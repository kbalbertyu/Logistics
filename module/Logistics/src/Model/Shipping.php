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
 * @property string amazonShippingLabel
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

    public function getProductLabelFile() {
        return Tools::ATTACHMENT_URL_PATH . $this->productLabel;
    }

    public function getAmazonShippingLabelFile() {
        return Tools::ATTACHMENT_URL_PATH . $this->amazonShippingLabel;
    }

    public function saveShipping($data) {
        $set = [
            'shippingCost' => $data['shippingCost'],
            'shippingFee' => $data['shippingFee'],
            'serviceFee' => $data['serviceFee'],
        ];
    }
}