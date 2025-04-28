<?php
namespace HesabePayment\Hesabe\Model\Checkout;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\UrlInterface;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE_KNET = 'hesabepayment_knet';
    const CODE_MPGS = 'hesabepayment_mpgs';

    private $urlBuilder;

    public function __construct(UrlInterface $urlBuilder)
    {
        $this->urlBuilder = $urlBuilder;
    }

    public function getConfig(): array
    {
        $url = $this->urlBuilder->getUrl('hesabe/payment/redirect');
        return [
            'payment' => [
                self::CODE_KNET => ['redirectUrl' => $url],
                self::CODE_MPGS => ['redirectUrl' => $url],
            ]
        ];
    }
}
