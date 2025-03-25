<?php

namespace HesabePayment\Hesabe\Controller\Index;

use HesabePayment\Hesabe\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
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
        ObjectManagerInterface $objectManager
    )
    {
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
            $new_order_status = $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')
                ->getValue('payment/knetmpgs/new_order_status');


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

                if (isset($new_order_status) && !empty($new_order_status)) {
                    $orderState = $new_order_status;
                    $order->setState($orderState)->setStatus($new_order_status);
                } else {
                    $orderState = Order::STATE_PROCESSING;
                    $order->setState($orderState)->setStatus(Order::STATE_PROCESSING);
                }

                $order->save();

                //get payment object from order object
                $payment = $order->getPayment();
                $payment->setLastTransId($result['Id']);
                $payment->setTransactionId($result['Id']);
                $payment->setAdditionalInformation(
                    [Transaction::RAW_DETAILS => (array)$result]
                );
                $formatedPrice = $order->getBaseCurrency()->formatTxt(
                    $order->getGrandTotal()
                );

                $message = __('The authorized amount is %1.', $formatedPrice);
                //get the object of builder class
                $trans = $this->_objectManager->create(
                    '\Magento\Sales\Model\Order\Payment\Transaction\Builder'
                );

                $transaction = $trans->setPayment($payment)
                    ->setOrder($order)
                    ->setTransactionId($result['Id'])
                    ->setAdditionalInformation(
                        [Transaction::RAW_DETAILS => (array)$result]
                    )
                    ->setFailSafe(true)
                    //build method creates the transaction and returns the object
                    ->build(Transaction::TYPE_CAPTURE);

                $payment->addTransactionCommentsToOrder(
                    $transaction,
                    $message
                );
                $payment->setParentTransactionId(null);
                $payment->save();
                $order->save();

                $transaction->save()->getTransactionId();
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
            } else {
                // There is a problem in the response we got
                $this->_objectManager->create(
                    '\Magento\Sales\Api\OrderManagementInterface'
                )->cancel($order->getEntityId());

                $orderState = Order::STATE_CANCELED;
                $order->setState($orderState)->setStatus(Order::STATE_CANCELED);
                $order->addStatusHistoryComment('Payment was not Sucessfully Placed with Transaction ID ' . $params["paymentId"], $orderState);
                $order->setIsVisibleOnFront(true);
                $order->save();
                $redirectUrl = $this->_url->getUrl('checkout/onepage/failure');
                $this->_responseFactory->create()->setRedirect($redirectUrl)->sendResponse();
                exit();
            }
        } catch (Exception $e) {
            //log errors here
        }
    }
}
