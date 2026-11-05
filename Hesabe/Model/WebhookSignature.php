<?php
declare(strict_types=1);

namespace HesabePayment\Hesabe\Model;

/**
 * Checks the `x-signature` header Hesabe puts on Checkout API 3.0 webhooks.
 *
 * As observed on the sandbox in November 2026: HMAC-SHA256 in lowercase hex, keyed with the
 * merchant's access code, over every top-level value of the JSON body except `blocks` (a
 * display layout), in the order Hesabe sends them, joined without a separator. Webhooks for
 * version 2.0 checkouts carry no signature, so they are never accepted.
 */
class WebhookSignature
{
    public function __construct(private readonly Config $config)
    {
    }

    /**
     * @param array<string, mixed> $webhook The decoded JSON body.
     */
    public function isValid(array $webhook, string $signature): bool
    {
        $key = $this->config->accessCode();
        $signature = strtolower(trim($signature));
        if ($key === '' || $signature === '') {
            return false;
        }

        unset($webhook['blocks']);
        foreach ($webhook as $value) {
            if (is_array($value) || is_object($value)) {
                return false;
            }
        }
        // implode() turns numbers and booleans into text the way Hesabe's PHP sender does.
        return hash_equals(hash_hmac('sha256', implode('', $webhook), $key), $signature);
    }
}
