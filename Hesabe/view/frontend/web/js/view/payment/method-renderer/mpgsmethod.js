define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',                
        'mage/url',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/redirect-on-success',
        'mage/mage',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function (Component, $, /*data,*/ url, additionalValidators, redirectOnSuccessAction) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'HesabePayment_Hesabe/payment/mpgs'
            },

            getCode: function() {
                return 'mpgs';
            },

            isActive: function() {
                return true;
            },

            /** Returns payment method instructions */
            getInstructions: function() {
                return window.checkoutConfig.payment.instructions[this.item.method];
            }
        });
    }
);