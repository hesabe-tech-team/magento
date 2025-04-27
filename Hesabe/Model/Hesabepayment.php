<?php
namespace HesabePayment\Hesabe\Model;

use Magento\Payment\Model\Method\AbstractMethod;

class Hesabepayment extends AbstractMethod
{
    const METHOD_CODE        = 'hesabepayment_mpgs';
    protected $_code         = self::METHOD_CODE;
    protected $_isOffline    = true;
    protected $_canUseCheckout = true;

    /**
     * After placeOrder(), Magento redirects here.
     */
    public function getOrderPlaceRedirectUrl(): string
    {
        return $this->_urlBuilder->getUrl('hesabe/payment/redirect');
    }
}
