<?php
declare(strict_types=1);

namespace HesabePayment\Hesabe\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * The Hesabe settings, read the way the rest of the module reads them: at the default scope,
 * from the `payment/knetmpgs` group.
 *
 * The stored field names are historical: the field labelled "Secret Key" is `encryptionkey`
 * and the one labelled "IV Key" is `secretkey`.
 */
class Config
{
    private const GROUP = 'payment/knetmpgs/';

    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    /** Hesabe's gateway host for the selected mode: "Live" (1) or "Demo" (0). */
    public function apiUrl(): string
    {
        return $this->value('payment_mode') === '1' ? 'https://api.hesabe.com' : 'https://sandbox.hesabe.com';
    }

    public function accessCode(): string
    {
        return $this->value('accesscode');
    }

    public function secretKey(): string
    {
        return $this->value('encryptionkey');
    }

    public function ivKey(): string
    {
        return $this->value('secretkey');
    }

    /** The status a paid order moves to ("New Order Status"); empty means Processing. */
    public function paidStatus(): string
    {
        return $this->value('new_order_status');
    }

    private function value(string $field): string
    {
        return trim((string) $this->scopeConfig->getValue(self::GROUP . $field));
    }
}
