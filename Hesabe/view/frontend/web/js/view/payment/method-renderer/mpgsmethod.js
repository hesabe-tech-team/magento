define([
    'Magento_Checkout/js/view/payment/default',
    'jquery',                
    'mage/url',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/action/redirect-on-success',
    'mage/mage',
    'Magento_Checkout/js/action/place-order',
    'Magento_Checkout/js/model/full-screen-loader'
], function (
    Component,
    $, 
    url,
    additionalValidators,
    redirectOnSuccessAction,
    mage,
    placeOrderAction,
    fullScreenLoader
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'HesabePayment_Hesabe/payment/mpgs'
        },

        getCode: function () {
            return 'hesabepayment_mpgs'; // This must match your method code
        },

        isActive: function () {
            return true;
        },

        getInstructions: function () {
            return window.checkoutConfig.payment.instructions
                ? window.checkoutConfig.payment.instructions[this.item.method]
                : '';
        },

        /**
         * Triggered when the Place Order button is clicked
         */
        placeOrder: function (data, event) {
            if (event) {
                event.preventDefault();
            }

            if (this.validate() && additionalValidators.validate()) {
                fullScreenLoader.startLoader();

                placeOrderAction(this.getData(), this.messageContainer).done(function () {
                    redirectOnSuccessAction.execute(); // Redirects to /hesabe/payment/redirect
                }).fail(function () {
                    fullScreenLoader.stopLoader();
                });

                return true;
            }

            return false;
        }
    });
});
