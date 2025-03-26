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

namespace HesabePayment\Hesabe\Plugin\Sales\Order\Email\Container;

use Magento\Checkout\Model\Session;
use Magento\Framework\Registry;

class OrderIdentityPlugin
{
    /**
     * @var Session $checkoutSession
     */
    protected $checkoutSession;

    /**
     * @var Registry
     */
    protected $_registry;

    public function __construct(
        Session $checkoutSession,
        Registry $registry
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->_registry = $registry;
    }

    /**
     * @param \Magento\Sales\Model\Order\Email\Container\OrderIdentity $subject
     * @param callable $proceed
     * @return bool
     */
    public function aroundIsEnabled(\Magento\Sales\Model\Order\Email\Container\OrderIdentity $subject, callable $proceed)
    {
        $hisabeEmailSend = $this->_registry->registry('hesabe_order_email');

        if ($hisabeEmailSend) {
            //$this->_registry->unregister('hesabe_order_email');
            return;
        }
        
        $returnValue = $proceed();
        return $returnValue;
    }
}