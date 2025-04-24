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
            template: 'HesabePayment_Hesabe/payment/hesabe' // adjust for mpgs version
        },

        getCode: function () {
            return this.item.method; // will be 'hesabepayment_knet' or 'hesabepayment_mpgs'
        },

        isActive: function () {
            return true;
        },

        placeOrder: function (data, event) {
            if (event) event.preventDefault();

            if (this.validate() && additionalValidators.validate()) {
                fullScreenLoader.startLoader();

                // include our extra config data
                var payload = this.getData();
                payload['additional_data'] = payload['additional_data'] || {};
                var cfg = window.checkoutConfig.payment[this.item.method];
                payload['additional_data']['integration_type'] = cfg.integrationType;
                payload['additional_data']['gateway']          = cfg.gateway;

                placeOrderAction(payload, this.messageContainer)
                    .done(function () {
                        redirectOnSuccessAction.execute();
                    })
                    .fail(function () {
                        fullScreenLoader.stopLoader();
                    });

                return true;
            }
            return false;
        }
    });
});
