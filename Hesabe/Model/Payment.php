<?php
namespace HesabePayment\Hesabe\Model;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Framework\DataObject;
use Magento\Quote\Api\Data\PaymentInterface;

class Payment extends AbstractMethod
{
    const METHOD_CODE     = 'hesabepayment_knet';

    protected $_code          = self::METHOD_CODE;
    protected $_isOffline     = false;
    protected $_canUseCheckout = true;
    protected $_canUseInternal = false;
    protected $_canUseForMultishipping = false;
    protected $_isInitializeNeeded = true;
    protected $_canSaveCc = false;
    protected $_formBlockType = \HesabePayment\Hesabe\Block\Form\Payment::class;
    protected $_infoBlockType = \HesabePayment\Hesabe\Block\Info\Payment::class;

    /**
     * @inheritdoc
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return parent::isAvailable($quote) && $this->getConfigData('active');
    }

    /**
     * @inheritdoc
     */
    public function assignData(DataObject $data)
    {
        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_object($additionalData)) {
            $additionalData = new DataObject($additionalData ?: []);
        }

        $info = $this->getInfoInstance();
        $info->setAdditionalInformation('payment_method', $additionalData->getData('hesabe_payment_method'));

        return $this;
    }
}
