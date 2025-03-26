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

namespace HesabePayment\Hesabe\Plugin\Sales\Order\Email\Sender;

use Magento\Sales\Model\Order;

class OrderSender
{
	/**
     * @var \Magento\Framework\Registry
     */
	protected $_registry;

	public function __construct(
		\Magento\Framework\Registry $registry
	) {
		$this->_registry = $registry;
	}

	public function beforeSend(Order\Email\Sender\OrderSender $subject, Order $order, $forceSyncMode = false)
    {
    	$pmethod = $order->getPayment()->getMethod();
    	if ($forceSyncMode == false) {
	    	if ($pmethod == 'hesabe' || $pmethod == 'mpgs') {
	    		$this->_registry->register('hesabe_order_email', '1');
			}
	    }

    	return [$order, $forceSyncMode];
    }
}