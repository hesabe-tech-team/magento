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
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;

class SendMailOnOrderSuccess implements ObserverInterface
{
    /**
     * @var OrderFactory
     */
    protected $orderModel;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var Session $checkoutSession
     */
    protected $checkoutSession;

    /**
     * @param OrderFactory $orderModel
     * @param OrderSender $orderSender
     * @param Session $checkoutSession
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        OrderFactory $orderModel,
        OrderSender $orderSender,
        Session $checkoutSession
    )
    {
        $this->orderModel = $orderModel;
        $this->orderSender = $orderSender;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $orderIds = $observer->getEvent()->getOrderIds();
        if(count($orderIds))
        {
            $this->checkoutSession->setForceOrderMailSentOnSuccess(true);
            $order = $this->orderModel->create()->load($orderIds[0]);
            $this->orderSender->send($order, true);
        }
    }
}
