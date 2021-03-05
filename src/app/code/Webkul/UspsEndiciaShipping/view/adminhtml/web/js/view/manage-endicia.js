/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_UspsEndiciaShipping
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
define([
    'jquery',
    'uiComponent',
    'underscore',
    'ko',
    "mage/template",
    'Magento_Ui/js/modal/alert'
], function ($, Component, _, ko, mageTemplate, alert) {
    'use strict';
    return Component.extend({
        accountType: ko.observable(''),
        accountId: ko.observable(''),
        accountBalance: ko.observable(''),
        postageAmount: ko.observable(''),
        defaults: {
            template: ''
        },
        initialize: function () {
            this._super();
            var self = this;
        },
        buyPostage: function () {
            var self = this;
            var url = window.endicia.buyUrl,
                itemData = {};
            itemData.form_key = window.FORM_KEY;
            itemData.amount = self.postageAmount();

            return $.ajax({
                showLoader: true,
                url: url,
                type: 'POST',
                data: itemData
            }).done(function (response) {
                if (response.error) {
                    alert({
                        title: 'Error!',
                        content: response.msg,
                        actions: {
                            always: function () {
                            }
                        }
                    });
                } else {
                    var response = response.response;
                    self.accountBalance(response.CertifiedIntermediary.PostageBalance);
                    self.loadAccountDetails();
                }
                self.postageAmount('');
            }).fail(function (response) {
                alert({
                    title: 'Error!',
                    content: response.msg,
                    actions: {
                        always: function () {
                        }
                    }
                });
            });
        },
        loadAccountDetails: function (event) {
            var self = this;
            var url = window.endicia.statusUrl,
                itemData = {};
            itemData.form_key = window.FORM_KEY;
            return $.ajax({
                showLoader: true,
                url: url,
                type: 'POST',
                data: itemData
            }).done(function (response) {
                if (response.error) {
                    alert({
                        title: 'Error!',
                        content: response.msg,
                        actions: {
                            always: function () {
                            }
                        }
                    });
                } else {
                    var response = response.response;
                    self.accountType(response.AccountType);
                    self.accountId(response.CertifiedIntermediary.AccountID);
                    self.accountBalance(response.CertifiedIntermediary.PostageBalance);
                }

            }).fail(function (response) {
                alert({
                    title: 'Error!',
                    content: response.msg,
                    actions: {
                        always: function () {
                        }
                    }
                });
            });
        },
    });
});