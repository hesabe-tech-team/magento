<?php
declare(strict_types=1);

namespace HesabePayment\Hesabe\Model;

use Magento\Framework\Lock\LockManagerInterface;
use Magento\Framework\Phrase;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;

/**
 * Applies what Hesabe reports about a payment to its Magento order.
 *
 * Two things report a payment: the customer's return and Hesabe's webhook. They arrive in
 * either order and the webhook can arrive more than once: it usually lands a few seconds
 * before the customer, and Hesabe retries it. So every
 * change happens under a per-order lock, and only to an order still awaiting payment. A paid
 * or cancelled order is never changed here; when Hesabe disagrees with it, the order gets a
 * comment for a person to act on.
 */
class Settlement
{
    /** The order was awaiting payment and is now paid. */
    public const PAID = 'paid';
    /** Hesabe confirms the payment and the order was already paid. */
    public const ALREADY_PAID = 'already_paid';
    /** The order was awaiting payment and is now cancelled. */
    public const CANCELLED = 'cancelled';
    /** Hesabe reports a failed payment and the order was already cancelled. */
    public const ALREADY_CANCELLED = 'already_cancelled';
    /** Hesabe has no final answer yet; nothing changed. */
    public const WAITING = 'waiting';
    /** Hesabe and the order disagree; nothing changed, the order has a comment. */
    public const CONFLICT = 'conflict';
    /** Another request is updating the order; nothing changed. */
    public const BUSY = 'busy';

    /** The payment method codes this module owns. */
    public const METHODS = [Payment::METHOD_CODE, Hesabepayment::METHOD_CODE];

    private const LOCK_SECONDS = 15;

    public function __construct(
        private readonly Config $config,
        private readonly OrderFactory $orderFactory,
        private readonly OrderManagementInterface $orderManagement,
        private readonly BuilderInterface $transactionBuilder,
        private readonly OrderSender $orderSender,
        private readonly LockManagerInterface $locks,
        private readonly LoggerInterface $logger
    ) {
    }

    /** True for orders paid with KNET or MPGS through this module. */
    public static function isHesabeOrder(Order $order): bool
    {
        return in_array($order->getPayment()?->getMethod(), self::METHODS, true);
    }

    /**
     * Applies a webhook whose signature has been checked. Everything read from it is covered
     * by the signature.
     *
     * @param array<string, mixed> $webhook The decoded body.
     */
    public function fromWebhook(Order $order, array $webhook): string
    {
        return $this->locked($order, fn (Order $fresh): string => $this->apply(
            $fresh,
            $webhook,
            (string) ($webhook['token'] ?? ''),
            __('Hesabe webhook')
        ));
    }

    /**
     * Marks the order paid from the customer's return, as the return page always has.
     *
     * @param array<string, mixed> $details What the return carried, kept on the transaction.
     * @return bool Whether the order is paid afterwards, by this return or by the webhook.
     */
    public function payFromReturn(Order $order, string $transactionId, array $details): bool
    {
        $this->locked($order, function (Order $fresh) use ($transactionId, $details): string {
            if (!$this->awaitsPayment($fresh)) {
                return self::ALREADY_PAID;
            }
            $this->markPaid(
                $fresh,
                $transactionId,
                $details,
                __('The authorized amount is %1.', $this->formattedTotal($fresh))
            );
            return self::PAID;
        });
        return $this->isPaid($this->fresh($order));
    }

    /**
     * Cancels the order after a failed return, unless it is no longer awaiting payment.
     */
    public function cancelFromReturn(Order $order, string $comment): void
    {
        $this->locked($order, function (Order $fresh) use ($comment): string {
            if (!$this->awaitsPayment($fresh)) {
                return self::CONFLICT;
            }
            $this->markCancelled($fresh, $comment);
            return self::CANCELLED;
        });
    }

    /**
     * Applies one transaction record, from the webhook or the enquiry, to a locked order.
     *
     * @param array<string, mixed> $record
     */
    private function apply(Order $order, array $record, string $transactionId, Phrase $source): string
    {
        $status = strtoupper(trim((string) ($record['status'] ?? '')));
        $amount = self::amount($record['amount'] ?? null);
        $total = self::amount($order->getGrandTotal());

        if ($status === 'SUCCESSFUL') {
            if ($this->isPaid($order)) {
                return self::ALREADY_PAID;
            }
            if (!$this->awaitsPayment($order)) {
                return $this->conflict($order, __(
                    'Hesabe reports this order paid (%1, %2 KWD, token %3), but the order is %4. '
                    . 'Refund the payment in the Hesabe Merchant Panel or reinstate the order.',
                    $source,
                    $amount ?? '?',
                    (string) ($record['token'] ?? ''),
                    $order->getState()
                ));
            }
            if ($amount === null || $amount !== $total) {
                return $this->conflict($order, __(
                    'Hesabe reports a payment of %1 KWD (%2, token %3) for this order of %4 KWD. '
                    . 'The order was not changed.',
                    $amount ?? '?',
                    $source,
                    (string) ($record['token'] ?? ''),
                    $total
                ));
            }
            $this->markPaid(
                $order,
                $transactionId !== '' ? $transactionId : (string) $order->getIncrementId(),
                self::details($record, $source),
                __('The authorized amount is %1. Confirmed by %2.', $this->formattedTotal($order), $source)
            );
            $this->sendOrderEmail($order);
            return self::PAID;
        }

        if ($status === 'FAILED') {
            if ($order->getState() === Order::STATE_CANCELED) {
                return self::ALREADY_CANCELLED;
            }
            if (!$this->awaitsPayment($order)) {
                return $this->conflict($order, __(
                    'Hesabe reports this payment failed (%1, token %2), but the order is %3. '
                    . 'Check it in the Hesabe Merchant Panel before shipping.',
                    $source,
                    (string) ($record['token'] ?? ''),
                    $order->getState()
                ));
            }
            $this->markCancelled($order, (string) __(
                'Hesabe reports the payment failed (%1, token %2).',
                $source,
                (string) ($record['token'] ?? '')
            ));
            return self::CANCELLED;
        }

        return self::WAITING;
    }

    /**
     * Marks the order paid with a capture transaction, as the return page always has.
     *
     * @param array<string, mixed> $details
     */
    private function markPaid(Order $order, string $transactionId, array $details, Phrase $message): void
    {
        $paidStatus = $this->config->paidStatus();
        if ($paidStatus !== '') {
            $order->setState($paidStatus)->setStatus($paidStatus);
        } else {
            $order->setState(Order::STATE_PROCESSING)->setStatus(Order::STATE_PROCESSING);
        }
        $order->save();

        $payment = $order->getPayment();
        $payment->setLastTransId($transactionId);
        $payment->setTransactionId($transactionId);
        $payment->setAdditionalInformation([Transaction::RAW_DETAILS => $details]);
        $transaction = $this->transactionBuilder
            ->setPayment($payment)
            ->setOrder($order)
            ->setTransactionId($transactionId)
            ->setAdditionalInformation([Transaction::RAW_DETAILS => $details])
            ->setFailSafe(true)
            ->build(Transaction::TYPE_CAPTURE);
        $payment->addTransactionCommentsToOrder($transaction, $message);
        $payment->setParentTransactionId(null);
        $payment->save();
        $order->save();
        $transaction->save();
    }

    private function markCancelled(Order $order, string $comment): void
    {
        $this->orderManagement->cancel((int) $order->getEntityId());
        $order = $this->fresh($order);
        if ($order->getState() !== Order::STATE_CANCELED) {
            // cancel() refuses some orders; the return page always forced the state as well.
            $order->setState(Order::STATE_CANCELED)->setStatus(Order::STATE_CANCELED);
        }
        $order->addCommentToStatusHistory($comment, Order::STATE_CANCELED);
        $order->save();
    }

    private function conflict(Order $order, Phrase $comment): string
    {
        $order->addCommentToStatusHistory($comment);
        $order->save();
        $this->logger->warning(sprintf('Hesabe order %s: %s', $order->getIncrementId(), $comment));
        return self::CONFLICT;
    }

    /**
     * Sends the order confirmation when the customer did not come back to trigger it. The
     * return path leaves it to the success page, which skips orders already emailed.
     */
    private function sendOrderEmail(Order $order): void
    {
        if ($order->getEmailSent()) {
            return;
        }
        try {
            $this->orderSender->send($order, true);
        } catch (\Exception $exception) {
            $this->logger->error(sprintf(
                'Hesabe order %s: order email failed: %s',
                $order->getIncrementId(),
                $exception->getMessage()
            ));
        }
    }

    /** Runs $work on a fresh copy of the order while holding the order's lock. */
    private function locked(Order $order, callable $work): string
    {
        $name = 'hesabe_order_' . $order->getIncrementId();
        if (!$this->locks->lock($name, self::LOCK_SECONDS)) {
            return self::BUSY;
        }
        try {
            return $work($this->fresh($order));
        } finally {
            $this->locks->unlock($name);
        }
    }

    /** Reads the order again, past the repository's cache, so a concurrent change is seen. */
    private function fresh(Order $order): Order
    {
        return $this->orderFactory->create()->load($order->getId());
    }

    private function isPaid(Order $order): bool
    {
        return (string) $order->getPayment()?->getLastTransId() !== '';
    }

    private function awaitsPayment(Order $order): bool
    {
        return !$this->isPaid($order)
            && in_array($order->getState(), [Order::STATE_NEW, Order::STATE_PENDING_PAYMENT], true);
    }

    private function formattedTotal(Order $order): string
    {
        return $order->getBaseCurrency()->formatTxt($order->getGrandTotal());
    }

    /** An amount as three-decimal text without separators, or null when it is not one. */
    private static function amount(mixed $value): ?string
    {
        $text = str_replace(',', '', trim((string) $value));
        return is_numeric($text) ? number_format((float) $text, 3, '.', '') : null;
    }

    /**
     * What the transaction keeps from a record: no customer details.
     *
     * @param array<string, mixed> $record
     * @return array<string, string>
     */
    private static function details(array $record, Phrase $source): array
    {
        $details = [
            'Payment Token' => $record['token'] ?? null,
            'Paid On' => $record['datetime'] ?? null,
            'Method' => $record['payment_type'] ?? null,
            'amount' => $record['amount'] ?? null,
            'Status' => $record['status'] ?? null,
            'Confirmed by' => (string) $source,
        ];
        return array_map('strval', array_filter($details, static fn ($value): bool => is_scalar($value) && $value !== ''));
    }
}
