<?php
namespace HesabePayment\Hesabe\Model;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Framework\UrlInterface;

class Payment extends AbstractMethod
{
    const METHOD_CODE = 'hesabepayment_knet';
    protected $_code              = self::METHOD_CODE;
    protected $_isOffline         = true;
    protected $_canUseCheckout    = true;

    private UrlInterface $urlBuilder;

    public function __construct(
        \Magento\Framework\Model\Context                      $context,
        \Magento\Framework\Registry                           $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory      $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory           $customAttributeFactory,
        \Magento\Payment\Model\Method\Logger                   $logger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb          $resourceCollection = null,
        UrlInterface                                          $urlBuilder,
        array                                                 $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Tell Magento where to redirect after placeOrder()
     */
    public function getOrderPlaceRedirectUrl(): string
    {
        return $this->urlBuilder->getUrl('hesabe/payment/redirect');
    }
}
