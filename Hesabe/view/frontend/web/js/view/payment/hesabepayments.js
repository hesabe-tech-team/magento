define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'hesabepayment_knet',
                component: 'HesabePayment_Hesabe/js/view/payment/method-renderer/hesabemethod'
            }
        );
        return Component.extend({});
    }
 );