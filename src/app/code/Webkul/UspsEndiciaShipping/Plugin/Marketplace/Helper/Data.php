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
namespace Webkul\UspsEndiciaShipping\Plugin\Marketplace\Helper;

class Data
{
    /**
     * function to run to change the return data of afterIsSeller.
     *
     * @param \Webkul\Marketplace\Helper\Data $helperData
     * @param array $result
     *
     * @return bool
     */
    public function afterGetControllerMappedPermissions(
        \Webkul\Marketplace\Helper\Data $helperData,
        $result
    ) {
        $result['endicia/acount/manage'] = 'endicia/acount/buypostage';
        $result['endicia/acount/manage'] = 'endicia/acount/accountstatus';
        $result['endicia/acount/config'] = 'endicia/acount/save';
        $result['endicia/acount/config'] = 'endicia/acount/newpassphrase';
        return $result;
    }
}
