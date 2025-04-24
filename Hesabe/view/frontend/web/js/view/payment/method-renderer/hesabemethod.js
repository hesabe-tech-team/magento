define([
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/action/place-order',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/action/redirect-on-success',
    'Magento_Checkout/js/model/full-screen-loader'
], function (
    Component,
    placeOrderAction,
    additionalValidators,
    redirectOnSuccessAction,
    fullScreenLoader
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'HesabePayment_Hesabe/payment/hesabe'
        },

        getCode: function () {
            return 'hesabepayment_knet'; // or 'hesabepayment_mpgs'
        },

        isActive: function () {
            return true;
        },

        /**
         * Called when "Place Order" is clicked
         */
        placeOrder: function (data, event) {
            if (event) {
                event.preventDefault();
            }

            if (this.validate() && additionalValidators.validate()) {
                fullScreenLoader.startLoader();
                placeOrderAction(this.getData(), this.messageContainer).done(function () {
                    redirectOnSuccessAction.execute(); // redirects to hesabe/payment/redirect
                }).fail(function () {
                    fullScreenLoader.stopLoader();
                });
                return true;
            }

            return false;
        }
    });
});
