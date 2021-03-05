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
namespace Webkul\UspsEndiciaShipping\Block\Adminhtml\Order\View;

/**
 * Add Endicia Refund button
 */
class ButtonList extends \Magento\Backend\Block\Widget\Button\ButtonList
{
    public function beforeSetLayout(\Magento\Shipping\Block\Adminhtml\View $view)
    {
        $view->addButton(
            'endicia_refund',
            [
                'label' => __('Cancel Shipping Label'),
                'class' => 'refund',
                'onclick' => '',
            ]
        );
    }
}
