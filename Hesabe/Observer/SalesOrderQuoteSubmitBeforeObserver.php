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
use Magento\Framework\Event\Manager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Hesabe Marketplace SalesOrderPlaceAfterObserver Observer Model.
 */
class SalesOrderQuoteSubmitBeforeObserver implements ObserverInterface
{
    /**
     * @var eventManager
     */
    protected $_eventManager;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @param Manager $eventManager
     * @param ObjectManagerInterface $objectManager
     * @param Session $checkoutSession
     */
    public function __construct(
        Manager $eventManager,
        ObjectManagerInterface $objectManager,
        Session $checkoutSession
    )
    {
        $this->_eventManager = $eventManager;
        $this->_objectManager = $objectManager;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * Sales Order Quote Submit Before event handler.
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $quote = $observer->getQuote();
        $this->_checkoutSession->setCurrentPaymentMethod($quote->getPayment()->getMethod());
    }
}
