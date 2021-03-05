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
class MailpeiceShape implements GenericInterface
{

    /**
     * Returns array to be used in multiselect on back-end
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            ['value' => '',  'label' => __('Select Mailpeice Shape')],
            ['value' => 'Card',  'label' => __('Card')],
            ['value' => 'Letter',  'label' => __('Letter')],
            ['value' => 'Parcel',  'label' => __('Parcel')],
            ['value' => 'LargeParcel',  'label' => __('Large Parcel')],
            ['value' => 'Flat',  'label' => __('Flat')],
            ['value' => 'FlatRateCardboardEnvelope',  'label' => __('Flat Rate Cardboard Envelope')],
            ['value' => 'FlatRateEnvelope',  'label' => __('Flat Rate Envelope')],
            ['value' => 'SmallFlatRateEnvelope',  'label' => __('Small Flat Rate Envelope')],
            ['value' => 'SmallFlatRateBox',  'label' => __('Small Flat Rate Box')],
            ['value' => 'MediumFlatRateBox',  'label' => __('Medium Flat RateBox')],
            ['value' => 'LargeFlatRateBox',  'label' => __('Large Flat Rate Box')],
            ['value' => 'RegionalRateBoxA',  'label' => __('Regional Rate BoxA')],
            ['value' => 'RegionalRateBoxB',  'label' => __('Regional Rate BoxB')],
        ];

        return $options;
    }
}
