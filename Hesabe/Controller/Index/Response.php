<?php

namespace HesabePayment\Hesabe\Controller\Index;

use HesabePayment\Hesabe\Helper\Data;
use HesabePayment\Hesabe\Model\Settlement;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\OrderFactory;

class Response extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    protected $resultPageFactory;
    protected $encrypthelper;
    protected $objectManager;
    protected $_orderFactory;
    protected $_orderRepository;
    protected $_responseFactory;
    protected $_registry;
    protected $_objectManager;
    protected $settlement;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Session $checkoutSession,
        OrderFactory $orderFactory,
        OrderRepositoryInterface $orderRepository,
        ResponseFactory $responseFactory,
        Registry $registry,
        Data $encrypthelper,
        ObjectManagerInterface $objectManager,
        Settlement $settlement
    )
    {
        $this->settlement = $settlement;
        $this->resultPageFactory = $resultPageFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_orderRepository = $orderRepository;
        $this->_responseFactory = $responseFactory;
        $this->_registry = $registry;
        $this->encrypthelper = $encrypthelper;
        $this->_objectManager = $objectManager;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $urlparams = $this->getRequest()->getParams();
            $orderId = $this->_checkoutSession->getLastRealOrderId();
            $order = $this->_orderFactory->create()->loadByIncrementId($orderId);
            $quoteId = $order->getQuoteId();

            $statusArray = array("1" => "Success", "0" => "Failure");
            $methodArray = array("1" => "KNet", "2" => "MasterCard / Visa");
            $encryptionkey = $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/knetmpgs/encryptionkey');
            $secretkey = $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/knetmpgs/secretkey');

            $decrypted_post_response = $this->encrypthelper->decrypt($urlparams['data'], $encryptionkey, $secretkey);
            $org_response = json_decode($decrypted_post_response, true);
            $params = $org_response['response'];
            $params['status'] = $org_response['status'];

            if ($params['status'] == 'true' || $params['status'] == '1') {


                $result = array();
                foreach ($params as $key => $value) {
                    if ($key == "paymentToken") {
                        $result["Payment Token"] = $value;
                    } elseif ($key == "paymentId") {
                        $result["Id"] = $value;
                    } elseif ($key == "paidOn") {
                        $result["Paid On"] = $value;
                    } elseif ($key == "status") {
                        $result["Status"] = $statusArray[$value];
                    } elseif ($key == "method") {
                        if ($value == "1") {
                            $result["Method"] = $methodArray[$value];
                        } elseif ($value == "2") {
                            $result["Method"] = $methodArray[$value];
                        } else {
                            $result["Method"] = $value;
                        }
                    } elseif ($value != "") {
                        $result[$key] = $value;
                    } else {
                    }
                }

                $paid = $this->settlement->payFromReturn($order, (string) $result['Id'], $result);
            } else {
                // There is a problem in the response we got.
                $paid = $this->settlement->cancelFromReturn(
                    $order,
                    'Payment was not Sucessfully Placed with Transaction ID ' . $params["paymentId"]
                );
            }

            // The return decides an order still awaiting payment. An order the webhook settled
            // first was checked again with the Transaction Enquiry, so the customer's page
            // follows Hesabe's latest status.
            if (!$paid) {
                $redirectUrl = $this->_url->getUrl('checkout/onepage/failure');
                $this->_responseFactory->create()->setRedirect($redirectUrl)->sendResponse();
                exit();
            }
            $this->_checkoutSession
                ->setLastQuoteId($quoteId)
                ->setLastSuccessQuoteId($quoteId)
                ->clearHelperData();

            if ($order) {
                $this->_checkoutSession->setLastOrderId($order->getId())
                    ->setLastRealOrderId($order->getIncrementId())
                    ->setLastOrderStatus($order->getStatus());

            }
            $this->_checkoutSession->setResponseData('success');

            $redirectUrl = $this->_url->getUrl('checkout/onepage/success');
            $this->_responseFactory->create()->setRedirect($redirectUrl)->sendResponse();
            exit();
        } catch (Exception $e) {
            //log errors here
        }
    }
}
