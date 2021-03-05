<?php
/**
 * Webkul Software
 *
 * @category Webkul
 * @package Webkul_UspsEndiciaShipping
 * @author Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license https://store.webkul.com/license.html
 */
namespace Webkul\UspsEndiciaShipping\Model\Source;

use Magento\Shipping\Model\Carrier\Source\GenericInterface;

/**
 * InsuranceType source
 */
class InsuranceType implements GenericInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $options = [
            ['value' => 'Endicia',  'label' => __('Endicia')],
            ['value' => 'UspsOnline',  'label' => __('UspsOnline')],
            ['value' => 'ThirdParty',  'label' => __('ThirdParty')],
        ];
        array_unshift($options, ['value' => 'ON', 'label' => __('None')]);
        
        return $options;
    }
}
