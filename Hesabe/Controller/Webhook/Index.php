<?php
declare(strict_types=1);

namespace HesabePayment\Hesabe\Controller\Webhook;

use HesabePayment\Hesabe\Model\Settlement;
use HesabePayment\Hesabe\Model\WebhookSignature;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;

/**
 * Receives Hesabe's webhook at `hesabe/webhook/index`: the checkout asks for it with every
 * payment, so the order is updated even when the customer never comes back to the shop.
 *
 * Only a webhook with a valid `x-signature` changes anything. Hesabe retries a delivery that
 * fails or takes too long (seen on the sandbox, November 2026), so the answer is 503 when the
 * order is busy and the next try should do the work.
 */
class Index implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public function __construct(
        private readonly Http $request,
        private readonly JsonFactory $jsonFactory,
        private readonly OrderFactory $orderFactory,
        private readonly WebhookSignature $signature,
        private readonly Settlement $settlement,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): Json
    {
        $webhook = json_decode((string) $this->request->getContent(), true, 512, JSON_BIGINT_AS_STRING);
        $signature = (string) $this->request->getHeader('X-Signature');
        if (!is_array($webhook) || !$this->signature->isValid($webhook, $signature)) {
            $this->logger->warning('Hesabe webhook refused: the signature is missing or does not match');
            return $this->answer(401, ['received' => false]);
        }

        $order = $this->orderFactory->create()->loadByIncrementId((string) ($webhook['reference_number'] ?? ''));
        if (!$order->getId() || !Settlement::isHesabeOrder($order)) {
            return $this->answer(404, ['received' => false]);
        }

        $outcome = $this->settlement->fromWebhook($order, $webhook);
        return $this->answer($outcome === Settlement::BUSY ? 503 : 200, ['received' => true, 'outcome' => $outcome]);
    }

    /** Hesabe's server posts here, so there is no form key; the signature authenticates it. */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @param array<string, mixed> $body
     */
    private function answer(int $status, array $body): Json
    {
        return $this->jsonFactory->create()->setHttpResponseCode($status)->setData($body);
    }
}
