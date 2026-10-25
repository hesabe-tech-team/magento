<?php
namespace HesabePayment\Hesabe\Model;

class Hesabepayment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const METHOD_CODE                       = 'mpgs';

    protected $_code                    	= self::METHOD_CODE;

    protected $_isOffline = true;
}