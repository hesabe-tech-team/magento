<?php
declare(strict_types=1);

namespace HesabePayment\Hesabe\Model;

use HesabePayment\Hesabe\Helper\Data;
use Magento\Framework\HTTP\Client\CurlFactory;

/**
 * Hesabe's Transaction Enquiry: what Hesabe recorded for an order reference.
 *
 * `GET api/transaction/<reference>?isOrderReference=1` with the access code. A 404, or a
 * "not found" message, means no payment for the reference reached Hesabe: the customer has
 * not paid yet, or 3-D Secure declined their card, which Hesabe does not record.
 */
class Enquiry
{
    private const TIMEOUT_SECONDS = 20;

    public function __construct(
        private readonly Config $config,
        private readonly CurlFactory $curlFactory
    ) {
    }

    /**
     * Every payment attempt Hesabe holds for the reference; empty when there is none.
     *
     * @return list<array<string, mixed>>
     * @throws EnquiryException when Hesabe cannot be asked or its answer cannot be read.
     */
    public function attempts(string $reference): array
    {
        $client = $this->curlFactory->create();
        $client->setTimeout(self::TIMEOUT_SECONDS);
        $client->addHeader('accessCode', $this->config->accessCode());
        $client->addHeader('Accept', 'application/json');
        $url = $this->config->apiUrl() . '/api/transaction/' . rawurlencode($reference) . '?isOrderReference=1';
        try {
            $client->get($url);
        } catch (\Exception $exception) {
            throw new EnquiryException('Hesabe could not be reached: ' . $exception->getMessage(), 0, $exception);
        }

        $status = $client->getStatus();
        if ($status === 404) {
            return [];
        }
        $body = trim($client->getBody());
        if ($status !== 200) {
            throw new EnquiryException("Hesabe answered HTTP {$status}");
        }

        // The enquiry answers in plain JSON; decrypt in case an account gets it encrypted, as
        // the checkout answers are.
        if ($body !== '' && ctype_xdigit($body)) {
            $body = (string) Data::decrypt($body, $this->config->secretKey(), $this->config->ivKey());
        }
        $answer = json_decode($body, true, 512, JSON_BIGINT_AS_STRING);
        if (!is_array($answer)) {
            throw new EnquiryException('Hesabe answered something other than JSON');
        }
        if (($answer['status'] ?? null) === false) {
            $message = (string) ($answer['message'] ?? '');
            if (stripos($message, 'not found') !== false) {
                return [];
            }
            throw new EnquiryException('Hesabe refused the enquiry: ' . $message);
        }

        // `results.data` lists every attempt for the reference; `data` repeats one of them.
        $records = $answer['results']['data'] ?? null;
        if (!is_array($records) || $records === []) {
            $records = is_array($answer['data'] ?? null) ? [$answer['data']] : [];
        }
        return array_values(array_filter(
            $records,
            static fn ($record): bool => is_array($record)
                && (string) ($record['reference_number'] ?? '') === $reference
        ));
    }
}
