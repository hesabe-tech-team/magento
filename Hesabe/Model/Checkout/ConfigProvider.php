<?php
namespace HesabePayment\Hesabe\Model\Checkout;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\UrlInterface;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE_KNET = 'hesabepayment_knet';
    const CODE_MPGS = 'hesabepayment_mpgs';

    /** @var UrlInterface */
    private $urlBuilder;

    public function __construct(UrlInterface $urlBuilder)
    {
        $this->urlBuilder = $urlBuilder;
    }

    public function getConfig(): array
    {
        return [
            'payment' => [
                self::CODE_KNET => [
                    'redirectUrl' => $this->urlBuilder->getUrl('hesabe/payment/redirect')
                ],
                self::CODE_MPGS => [
                    'redirectUrl' => $this->urlBuilder->getUrl('hesabe/payment/redirect')
                ]
            ]
        ];
    }
}
