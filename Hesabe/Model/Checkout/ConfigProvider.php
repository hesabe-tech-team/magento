<?php
namespace HesabePayment\Hesabe\Model\Checkout;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\UrlInterface;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'hesabe';

    private $urlBuilder;

    public function __construct(UrlInterface $urlBuilder)
    {
        $this->urlBuilder = $urlBuilder;
    }

    public function getConfig(): array
    {
        return [
            'payment' => [
                self::CODE => [
                    'redirectUrl' => $this->urlBuilder->getUrl('hesabe/payment/redirect')
                ]
            ]
        ];
    }
}
