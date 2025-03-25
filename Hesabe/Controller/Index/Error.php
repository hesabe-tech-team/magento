<?php
namespace HesabePayment\Hesabe\Controller\Index;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;

class Error extends \Magento\Framework\App\Action\Action
{

    protected $resultPageFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Session $checkoutSession
     * @param OrderFactory $orderFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param ResponseFactory $responseFactory
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Session $checkoutSession,
        OrderFactory $orderFactory,
        OrderRepositoryInterface $orderRepository,
        ResponseFactory $responseFactory,
        Registry $registry
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_orderRepository = $orderRepository;
        $this->_responseFactory = $responseFactory;
        $this->_registry = $registry;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $orderId = $this->_checkoutSession->getLastRealOrderId();
        $order = $this->_orderFactory->create()->loadByIncrementId($orderId);


        $this->_objectManager->create(
            '\Magento\Sales\Api\OrderManagementInterface'
        )->cancel($order->getEntityId());
                        
        $orderState = Order::STATE_CANCELED;
        $order->setState($orderState)->setStatus(Order::STATE_CANCELED);
        $order->addStatusHistoryComment('Gateway has declined the payment', $orderState);
        $order->setIsVisibleOnFront(true);
        $order->save();

        $this->messageManager->addError( __('Gateway has declined the payment') );
        
        $redirectUrl = $this->_url->getUrl('checkout/onepage/failure');
        $this->_responseFactory->create()->setRedirect($redirectUrl)->sendResponse();
        exit();
    }
}
