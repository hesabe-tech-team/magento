<?php
/**
 * Hesabe Software.
 *
 * @category  Hesabe
 * @package   Hesabe_Marketplace
 * @author    Hesabe
 * @copyright Copyright (c) 2019-2020 Hesabe (https://hesabe.com)
 *
 */

namespace HesabePayment\Hesabe\Observer;

use HesabePayment\Hesabe\Helper\Data;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\Event\Manager;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Session\SessionManager;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Unserialize\Unserialize;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;

error_log("LOADED FILE: " . __FILE__);

/**
 * Hesabe Marketplace SalesOrderPlaceAfterObserver Observer Model.
 */
class SalesOrderSuccessObserver implements ObserverInterface
{   /**
        * @var OrderFactory
    */
    protected $_orderFactory;
    
    /**
     * @var eventManager
     */
    protected $_eventManager;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * [$_coreSession description].
     *
     * @var SessionManager
     */
    protected $_coreSession;

    /**
     * @var QuoteRepository
     */
    protected $_quoteRepository;

    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * @var ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var Unserialize
     */
    protected $_unserializer;

    /**
     * @var DateTime
     */
    protected $_date;

    protected $encrypthelper;

    protected $responseFactory;

    protected $url;

    protected $messageManager;

    protected $orderSender;

    /**
     * @param Manager $eventManager
     * @param ObjectManagerInterface $objectManager
     * @param Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param SessionManager $coreSession
     * @param QuoteRepository $quoteRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param ProductRepositoryInterface $productRepository
     * @param MarketplaceHelper $marketplaceHelper
     * @param Unserialize $unserializer
     * @param DateTime $date
     */
    public function __construct(
        Manager $eventManager,
        ObjectManagerInterface $objectManager,
        Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        SessionManager $coreSession,
        QuoteRepository $quoteRepository,
        OrderRepositoryInterface $orderRepository,
        CustomerRepositoryInterface $customerRepository,
        ProductRepositoryInterface $productRepository,
        Unserialize $unserializer,
        OrderFactory $orderFactory,
        DateTime $date,
        Data $encrypthelper,
        ResponseFactory $responseFactory,
        UrlInterface $url,
        ManagerInterface $messageManager,
        OrderSender $orderSender
    ) {
        $this->_eventManager = $eventManager;
        $this->_objectManager = $objectManager;
        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        $this->_coreSession = $coreSession;
        $this->_quoteRepository = $quoteRepository;
        $this->_orderRepository = $orderRepository;
        $this->_customerRepository = $customerRepository;
        $this->_productRepository = $productRepository;
        $this->_unserializer = $unserializer;
        $this->_orderFactory = $orderFactory;
        $this->encrypthelper = $encrypthelper;
        $this->responseFactory = $responseFactory;
        $this->url = $url;
        $this->_date = $date;
        $this->messageManager = $messageManager;
        $this->orderSender = $orderSender;
    }

    /**
     * Sales Order Place After event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $curPay = $this->_checkoutSession->getCurrentPaymentMethod();
        $responseData = $this->_checkoutSession->getResponseData();
        if ($this->_checkoutSession->getResponseData() != 'success' && $curPay == 'hesabe') {
            try {
                $oderId = $this->_checkoutSession->getLastRealOrderId();
                $order = $this->_orderFactory->create()->loadByIncrementId($oderId);
                $base_url = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getBaseUrl();
                $mode = $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/knetmpgs/payment_mode');
                $encryptionkey = $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/knetmpgs/encryptionkey');
                $secretkey = $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/knetmpgs/secretkey');

                $accesscode = $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/knetmpgs/accesscode');
                $paymentoption = 1;
                $apiUrl = "https://sandbox.hesabe.com";
                if ($mode == 1) {
                    $apiUrl = "https://api.hesabe.com";
                }
                $post_url = $apiUrl . '/checkout';
                $MerchantCode = $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/knetmpgs/merchant_code');
                $responseUrl = $base_url . 'hesabe/index/response';

                $post_values = [
                    "merchantCode" => $MerchantCode,
                    "amount" => number_format(round($order->getGrandTotal(), 3), 3),
                    "responseUrl" => $responseUrl,
                    "failureUrl" => $responseUrl,
                    "paymentType" => $paymentoption,
                    "orderReferenceNumber" => $order->getIncrementId(),
                    "variable1" => $oderId,
                    "version" => '2.0'
                ];

                $post_string = json_encode($post_values);
                $encrypted_post_string = $this->encrypthelper->encrypt($post_string, $encryptionkey, $secretkey);

                $encrypted_post_string = 'data=' . $encrypted_post_string;

                $header = [];

                $header[] = 'accessCode: ' . $accesscode;

                $curl = curl_init($post_url);
                if ($mode == 1) {
                    curl_setopt($curl, CURLOPT_PORT, 443);
                }
                curl_setopt($curl, CURLOPT_HEADER, 1);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
                curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 12);
                curl_setopt($curl, CURLOPT_TIMEOUT, 12);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $encrypted_post_string);
                $post_response = curl_exec($curl);
                curl_close($curl); // close curl object

                list($responsheader, $responsebody) = explode("\r\n\r\n", $post_response, 2);
                
                file_put_contents(BP . '/var/log/hesabe_debug.log', print_r([
                    'request_url' => $post_url,
                    'headers' => $header,
                    'payload' => $post_string,
                    'encrypted_payload' => $encrypted_post_string,
                    'raw_response' => $post_response,
                    'response_body' => $responsebody
                ], true), FILE_APPEND);
                
                $decrypted_post_response = $this->encrypthelper->decrypt($responsebody, $encryptionkey, $secretkey);

                $decode_response = json_decode($decrypted_post_response);
                
                file_put_contents(BP . '/var/log/hesabe_debug.log', print_r($decode_response, true), FILE_APPEND);

                if ($decode_response->status) {
                    $payToken = $decode_response->response->data;
                    $payURL = $apiUrl . '/payment?data=' . $payToken;
                    header("location:" . $payURL);
                    exit;
                } else {
                    $this->messageManager->addError(__("Payment status not return."));
                    $redirectionUrl = $this->url->getUrl('checkout/cart');
                    $this->responseFactory->create()->setRedirect($redirectionUrl)->sendResponse();
                    return $this;
                }
            } catch (\Exception $e) {
                $this->messageManager->addError(__("Payment capturing error."));
                $redirectionUrl = $this->url->getUrl('checkout/cart');
                $this->responseFactory->create()->setRedirect($redirectionUrl)->sendResponse();
                return $this;
            }
        } elseif ($this->_checkoutSession->getResponseData() != 'success' && $curPay == 'mpgs') {
            try {
                $oderId = $this->_checkoutSession->getLastRealOrderId();
                $order = $this->_orderFactory->create()->loadByIncrementId($oderId);
                $base_url = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getBaseUrl();
                $mode = $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/knetmpgs/payment_mode');
                $encryptionkey = $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/knetmpgs/encryptionkey');
                $secretkey = $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/knetmpgs/secretkey');
                $accesscode = $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/knetmpgs/accesscode');
                $paymentoption = 2;

                $apiUrl = "https://sandbox.hesabe.com";
                if ($mode == 1) {
                    $apiUrl = "https://api.hesabe.com";
                }
                $post_url = $apiUrl . '/checkout';

                $MerchantCode = $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/knetmpgs/merchant_code');
                $responseUrl = $base_url . 'hesabe/index/response';

                $post_values = [
                    "merchantCode" => $MerchantCode,
                    "amount" => number_format(round($order->getGrandTotal(), 3), 3),
                    "responseUrl" => $responseUrl,
                    "failureUrl" => $responseUrl,
                    "paymentType" => $paymentoption,
                    "orderReferenceNumber" => $order->getIncrementId(),
                    "variable1" => $oderId,
                    "version" => '2.0'
                ];

                $post_string = json_encode($post_values);

                $encrypted_post_string = $this->encrypthelper->encrypt($post_string, $encryptionkey, $secretkey);

                $encrypted_post_string = 'data=' . $encrypted_post_string;

                $header = [];

                $header[] = 'accessCode: ' . $accesscode;

                $curl = curl_init($post_url);
                if ($mode == 1) {
                    curl_setopt($curl, CURLOPT_PORT, 443);
                }
                curl_setopt($curl, CURLOPT_HEADER, 1);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
                curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 12);
                curl_setopt($curl, CURLOPT_TIMEOUT, 12);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $encrypted_post_string);
                $post_response = curl_exec($curl);
                curl_close($curl); // close curl object

                list($responsheader, $responsebody) = explode("\r\n\r\n", $post_response, 2);

                $decrypted_post_response = $this->encrypthelper->decrypt($responsebody, $encryptionkey, $secretkey);

                $decode_response = json_decode($decrypted_post_response);

                if ($decode_response->status) {
                    $payToken = $decode_response->response->data;
                    $payURL = $apiUrl . '/payment?data=' . $payToken;
                    header("location:" . $payURL);
                    exit;
                } else {
                    $this->messageManager->addError(__("Payment status not return."));
                    $redirectionUrl = $this->url->getUrl('checkout/cart');
                    $this->responseFactory->create()->setRedirect($redirectionUrl)->sendResponse();
                    return $this;
                }
            } catch (\Exception $e) {
                $this->messageManager->addError(__("Payment capturing error."));
                $redirectionUrl = $this->url->getUrl('checkout/cart');
                $this->responseFactory->create()->setRedirect($redirectionUrl)->sendResponse();
                return $this;
            }
        } else {
            $this->_checkoutSession->unsResponseData();
            $this->_checkoutSession->unsCurrentPaymentMethod();
        }

        if ($responseData == 'success' && ($curPay == 'hesabe' || $curPay == 'mpgs')) {
            $orderIds = $observer->getEvent()->getOrderIds();
            if (count($orderIds)) {
                $this->_checkoutSession->setForceOrderMailSentOnSuccess(true);
                $order = $this->_orderFactory->create()->load($orderIds[0]);
                $this->orderSender->send($order, true);
            }
        }
    }
}
