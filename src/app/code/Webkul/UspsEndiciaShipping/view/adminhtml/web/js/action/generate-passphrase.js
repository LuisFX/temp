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
    "jquery",
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/modal/alert',
    "jquery/ui",
    "mage/translate"
], function ($, modal, alert) {
    'use strict';
    $.widget('mage.generatePassphrase', {
        options: {},
        _create: function () {
            var self = this,
                itemData = {};
            itemData.form_key = window.FORM_KEY;

            var options = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                buttons: [{
                    text: $.mage.__('Continue'),
                    class: '',
                    click: function () {
                        var slef1 = this;
                        itemData.passphrase = $('#passphrase').val();
                        $.ajax({
                            showLoader: true,
                            url: self.options.generateUrl,
                            type: 'POST',
                            data: itemData
                        }).done(function (response) {
                            slef1.closeModal();
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
                                slef1.closeModal();
                                alert({
                                    title: 'New Passphrase',
                                    content: 'This is new passphrase ' + response.passphrase + ' (Please copy and paste in Passphrase field)',
                                    actions: {
                                        always: function () {
                                        }
                                    }
                                });
                            }
                        }).fail(function (response) {
                            slef1.closeModal();
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
                },
                {
                    text: $.mage.__('Cancel'),
                    class: '',
                    click: function () {
                        this.closeModal();
                    }
                }]
            };

            var popup = modal(options, $('#popup-mpdal'));
            //start generating passphrase
            $(self.options.generateButton).on('click', function () {

                $("#popup-mpdal").modal("openModal");
                //self._generate();
            });
        },
        _generate: function () {
            var self = this;
            new Ajax.Request(self.options.startUrl, {
                method: 'post',
                data: { form_key: window.FORM_KEY },
                onSuccess: function (transport) {
                    var response = $.parseJSON(transport.responseText);
                    if (response.error) {
                        $('<div />').html(response.message).modal({
                            title: $.mage.__('Server Error'),
                            autoOpen: true,
                            buttons: [{
                                text: 'OK',
                                attr: {
                                    'data-action': 'cancel'
                                },
                                'class': 'action-primary',
                                click: function () {
                                    this.closeModal();
                                }
                            }]
                        });
                    } else {
                        $('<div />').html(response.message).modal({
                            title: $.mage.__('New Passphrase Generated'),
                            autoOpen: true,
                            buttons: [{
                                text: 'OK',
                                attr: {
                                    'data-action': 'cancel'
                                },
                                'class': 'action-primary',
                                click: function () {
                                    this.closeModal();
                                }
                            }]
                        });
                    }
                }
            });
        },
    });
    return $.mage.generatePassphrase;
});