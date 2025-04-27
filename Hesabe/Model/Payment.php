<?php
namespace HesabePayment\Hesabe\Model;

use Magento\Payment\Model\Method\AbstractMethod;

class Payment extends AbstractMethod
{
    const METHOD_CODE        = 'hesabepayment_knet';

    protected $_code         = self::METHOD_CODE;
    protected $_isOffline    = true;
    protected $_canUseCheckout = true;

    /**
     * After placeOrder(), Magento will redirect here.
     */
    public function getOrderPlaceRedirectUrl(): string
    {
        return $this->_urlBuilder->getUrl('hesabe/payment/redirect');
    }
}
