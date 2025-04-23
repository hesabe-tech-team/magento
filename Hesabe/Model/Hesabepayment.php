<?php
namespace HesabePayment\Hesabe\Model;

use Magento\Payment\Model\Method\AbstractMethod;

class Hesabepayment extends AbstractMethod
{
    const METHOD_CODE = 'hesabepayment_mpgs';

    protected $_code = self::METHOD_CODE;

    protected $_isOffline = true;
}
