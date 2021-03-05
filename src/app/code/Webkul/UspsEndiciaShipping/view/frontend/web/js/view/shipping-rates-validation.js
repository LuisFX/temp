/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_UspsEndiciaShipping
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/shipping-rates-validator',
        'Magento_Checkout/js/model/shipping-rates-validation-rules',
        'Webkul_UspsEndiciaShipping/js/model/shipping-rates-validator',
        'Webkul_UspsEndiciaShipping/js/model/shipping-rates-validation-rules'
    ],
    function (
        Component,
        defaultShippingRatesValidator,
        defaultShippingRatesValidationRules,
        endiciaShippingRatesValidator,
        endiciaShippingRatesValidationRules
    ) {
        'use strict';
        defaultShippingRatesValidator.registerValidator('mpendicia', endiciaShippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('mpendicia', endiciaShippingRatesValidationRules);
        return Component;
    }
);
