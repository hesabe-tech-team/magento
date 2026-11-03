<?php
declare(strict_types=1);

namespace HesabePayment\Hesabe\Plugin\Sales\Block\Adminhtml\Order;

use HesabePayment\Hesabe\Controller\Adminhtml\Order\CheckStatus;
use HesabePayment\Hesabe\Model\Settlement;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Escaper;
use Magento\Framework\View\LayoutInterface;
use Magento\Sales\Block\Adminhtml\Order\View;
use Magento\Sales\Model\Order;

/**
 * Adds "Check Payment Status" to the order page for Hesabe orders that are Pending (Hesabe
 * has not confirmed the payment) or Processing (paid, to compare with Hesabe's record).
 */
class CheckStatusButton
{
    private const STATES = [Order::STATE_NEW, Order::STATE_PENDING_PAYMENT, Order::STATE_PROCESSING];

    public function __construct(
        private readonly AuthorizationInterface $authorization,
        private readonly Escaper $escaper
    ) {
    }

    /**
     * @return array{0: LayoutInterface}
     */
    public function beforeSetLayout(View $subject, LayoutInterface $layout): array
    {
        $order = $subject->getOrder();
        if (
            $order instanceof Order
            && Settlement::isHesabeOrder($order)
            && in_array($order->getState(), self::STATES, true)
            && $this->authorization->isAllowed(CheckStatus::ADMIN_RESOURCE)
        ) {
            $url = $subject->getUrl('hesabe/order/checkStatus', ['order_id' => $order->getId()]);
            $subject->addButton('hesabe_check_status', [
                'label' => __('Check Payment Status'),
                'onclick' => sprintf("setLocation('%s')", $this->escaper->escapeJs($url)),
            ]);
        }
        return [$layout];
    }
}
