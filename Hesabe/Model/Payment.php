<?php
namespace HesabePayment\Hesabe\Model;

use Magento\Payment\Model\Method\AbstractMethod;

class Payment extends AbstractMethod
{
    const METHOD_CODE     = 'hesabepayment_knet';

    protected $_code          = self::METHOD_CODE;
    protected $_isOffline     = true;
    protected $_canUseCheckout = true;
    protected $_redirectUrl   = 'hesabe/payment/redirect';
}
