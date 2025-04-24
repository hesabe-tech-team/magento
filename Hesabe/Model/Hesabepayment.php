<?php
namespace HesabePayment\Hesabe\Model;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\Method\Logger;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Framework\UrlInterface;

class Hesabepayment extends AbstractMethod
{
    const METHOD_CODE = 'hesabepayment_mpgs';

    protected $_code              = self::METHOD_CODE;
    protected $_isOffline         = true;
    protected $_canUseCheckout    = true;

    private UrlInterface $urlBuilder;

    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        PaymentHelper $paymentData,
        Logger $logger,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        UrlInterface $urlBuilder,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Redirect URL after placeOrder()
     */
    public function getOrderPlaceRedirectUrl(): string
    {
        return $this->urlBuilder->getUrl('hesabe/payment/redirect');
    }
}
