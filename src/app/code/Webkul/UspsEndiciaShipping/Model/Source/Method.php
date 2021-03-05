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
namespace Webkul\UspsEndiciaShipping\Model\Source;

use Magento\Shipping\Model\Carrier\Source\GenericInterface;

/**
 * Generic source
 */
class Method implements GenericInterface
{

    /**
     * Returns array to be used in multiselect on back-end
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            ['value' => 'PriorityExpress',  'label' => __('Priority Mail Express')],
            ['value' => 'First',  'label' => __('First Class Package Service')],
            ['value' => 'LibraryMail',  'label' => __('Library Mail')],
            ['value' => 'MediaMail',  'label' => __('Media Mail')],
            ['value' => 'Priority',  'label' => __('Priority Mail')],
            ['value' => 'FirstClassPackageInternationalService',
            'label' => __('First Class Package International Service')],
            ['value' => 'PriorityMailInternational',  'label' => __('Priority Mail International')],
            ['value' => 'ExpressMailInternational',  'label' => __('Priority Mail Express International')],
            ['value' => 'GXG',  'label' => __('Global Express Guaranteed')],
            ['value' => 'EndiciaGlobalService',  'label' => __('Endicia Global Service')],
        ];

        return $options;
    }
}
