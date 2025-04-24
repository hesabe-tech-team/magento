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
            template: 'HesabePayment_Hesabe/payment/mpgs'
        },

        /**
         * Must match your code in etc/config.xml & Model/Hesabepayment::METHOD_CODE
         */
        getCode: function () {
            return 'hesabepayment_mpgs';
        },

        isActive: function () {
            return true;
        },

        /**
         * Called when the customer clicks “Place Order”
         */
        placeOrder: function (data, event) {
            if (event) {
                event.preventDefault();
            }

            if (this.validate() && additionalValidators.validate()) {
                fullScreenLoader.startLoader();

                // 1) build the default Magento payload
                var payload = this.getData();

                // 2) stash in our extra two fields
                payload['additional_data'] = payload['additional_data'] || {};
                var cfg = window.checkoutConfig.payment[this.item.method] || {};
                payload['additional_data']['integration_type'] = cfg.integrationType;
                payload['additional_data']['gateway']          = cfg.gateway;

                // 3) fire the order + redirect
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
