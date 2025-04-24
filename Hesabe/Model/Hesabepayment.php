<?php
namespace HesabePayment\Hesabe\Model;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Payment\Model\Method\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Model\Method\Plugin\Validator;
use Magento\Framework\UrlInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Payment\Model\Method\AbstractMethod;

class Hesabepayment extends AbstractMethod
{
    const METHOD_CODE = 'hesabepayment_mpgs';

    protected $_code              = self::METHOD_CODE;
    protected $_isOffline         = true;
    protected $_canUseCheckout    = true;

    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        Validator $methodValidator,
        UrlInterface $urlBuilder,
        FormatInterface $localeFormat,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $logger,
            $scopeConfig,
            $methodValidator,
            $urlBuilder,
            $localeFormat,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Magento will redirect the customer here after placeOrder()
     */
    public function getOrderPlaceRedirectUrl(): string
    {
        return $this->_urlBuilder->getUrl('hesabe/payment/redirect');
    }
}
