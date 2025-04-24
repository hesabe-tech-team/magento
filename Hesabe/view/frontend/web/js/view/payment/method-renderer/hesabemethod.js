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
            return 'hesabepayment_knet';
        },

        isActive: function () {
            return true;
        },

        placeOrder: function (data, event) {
            if (event) event.preventDefault();

            if (this.validate() && additionalValidators.validate()) {
                fullScreenLoader.startLoader();

                var self = this;
                placeOrderAction(self.getData(), self.messageContainer)
                  .fail(function () {
                    fullScreenLoader.stopLoader();
                  })
                  .done(function () {
                    fullScreenLoader.stopLoader();

                    // Pull the redirectUrl you injected in ConfigProvider
                    var url = window.checkoutConfig
                      .payment[self.getCode()]
                      .redirectUrl;
                    // Now send the browser to the Hesabe gateway
                    redirectOnSuccessAction.execute(url);
                  });

                return true;
            }
            return false;
        }
    });
});
