<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_UspsEndiciaShipping
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Webkul\UspsEndiciaShipping\Api;

/**
 * Interface for manageing endicia api requests.
 * @api
 */
interface EndiciaManagementInterface extends \Magento\Framework\Api\MetadataServiceInterface
{
    /**
     * Retrieve endicia account information
     *
     * @param string $accountInfo
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAccountStatus();

    /**
     * Buy Postage request
     *
     * @param int $amount
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function buyPostage($amount);

    /**
     * Create Label Refund request
     *
     * @param array $trackings
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRefundPostage($trackings);
}
