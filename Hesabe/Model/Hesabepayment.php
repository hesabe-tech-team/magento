<?php
namespace HesabePayment\Hesabe\Model;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Framework\UrlInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Payment\Model\Method\Plugin\Validator as MethodValidator;

class Hesabepayment extends AbstractMethod
{
    const METHOD_CODE = 'hesabepayment_mpgs';

    protected $_code = self::METHOD_CODE;
    protected $_isOffline = true;
    protected $_canUseCheckout = true;

    protected $_urlBuilder;
    protected $_logger;
    protected $_methodValidator;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        Logger $logger,
        UrlInterface $urlBuilder,
        MethodValidator $methodValidator,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
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

        $this->_urlBuilder = $urlBuilder;
        $this->_logger = $logger;
        $this->_methodValidator = $methodValidator;
    }

    /**
     * After placeOrder(), Magento redirects here.
     */
    public function getOrderPlaceRedirectUrl(): string
    {
        return $this->_urlBuilder->getUrl('hesabe/payment/redirect');
    }
}
