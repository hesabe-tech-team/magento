<?php
namespace HesabePayment\Hesabe\Model;

class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const METHOD_CODE                       = 'hesabe';

    protected $_code                    	= self::METHOD_CODE;

    protected $_isOffline = true;
}