<?php
declare(strict_types=1);

namespace HesabePayment\Hesabe\Controller\Adminhtml\Order;

use HesabePayment\Hesabe\Model\Settlement;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Sales\Model\OrderFactory;

/**
 * "Check Payment Status" on the order page: asks Hesabe's Transaction Enquiry about the
 * order and applies the answer, then shows what happened.
 *
 * A GET with the admin URL key, like Magento's own "Get Payment Update" button.
 */
class CheckStatus extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'HesabePayment_Hesabe::check_status';

    public function __construct(
        Context $context,
        private readonly OrderFactory $orderFactory,
        private readonly Settlement $settlement
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $redirect = $this->resultRedirectFactory->create();
        $order = $this->orderFactory->create()->load((int) $this->getRequest()->getParam('order_id'));
        if (!$order->getId() || !Settlement::isHesabeOrder($order)) {
            $this->messageManager->addErrorMessage(__('This order was not paid with Hesabe.'));
            return $redirect->setPath('sales/order/');
        }

        $outcome = $this->settlement->confirm($order);
        match ($outcome) {
            Settlement::PAID => $this->messageManager->addSuccessMessage(
                __('Hesabe confirms the payment. The order is now marked paid.')
            ),
            Settlement::ALREADY_PAID => $this->messageManager->addSuccessMessage(
                __('Hesabe confirms the payment. The order was already marked paid.')
            ),
            Settlement::CANCELLED => $this->messageManager->addNoticeMessage(
                __('Hesabe reports the payment failed. The order is now cancelled.')
            ),
            Settlement::ALREADY_CANCELLED => $this->messageManager->addNoticeMessage(
                __('Hesabe reports the payment failed. The order was already cancelled.')
            ),
            Settlement::WAITING => $this->messageManager->addNoticeMessage(
                __('Hesabe has no completed payment for this order yet. Nothing was changed.')
            ),
            Settlement::CONFLICT => $this->messageManager->addWarningMessage(
                __('Hesabe\'s record does not match this order. Nothing was changed; see the order comments.')
            ),
            Settlement::UNREACHABLE => $this->messageManager->addErrorMessage(
                __('Hesabe could not be reached. Nothing was changed. Try again in a minute.')
            ),
            default => $this->messageManager->addErrorMessage(
                __('The order is being updated by another request. Try again in a minute.')
            ),
        };
        return $redirect->setPath('sales/order/view', ['order_id' => $order->getId()]);
    }
}
