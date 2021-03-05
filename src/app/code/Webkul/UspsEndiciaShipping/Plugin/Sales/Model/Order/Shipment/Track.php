<?php
/**
 * Webkul Software.
 *
 * @category Webkul
 * @package Webkul_UspsEndiciaShipping
 * @author Webkul
 * @copyright Copyright (c) WebkulSoftware Private Limited (https://webkul.com)
 * @license https://store.webkul.com/license.html
 *
 */

namespace Webkul\UspsEndiciaShipping\Plugin\Sales\Model\Order\Shipment;

class Track
{
    public function afterGetNumber(\Magento\Sales\Model\Order\Shipment\Track $subject, $result)
    {
        if ($subject->getId() && $subject->getRefundRequested()) {
            return __('Label Cancelled');
        }
        return $result;
    }

    /**
     * Check whether custom carrier was used for this track
     *
     * @return bool
     */
    public function afterIsCustom(\Magento\Sales\Model\Order\Shipment\Track $subject, $result)
    {
        if ($subject->getId() && $subject->getRefundRequested()) {
            return true;
        }

        return $result;
    }
}
