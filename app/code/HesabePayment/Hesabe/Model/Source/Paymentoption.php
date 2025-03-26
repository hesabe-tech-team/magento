<?php
namespace HesabePayment\Hesabe\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class Paymentoption implements ArrayInterface
{
 public function toOptionArray()
 {
  return [
    ['value' => '1', 'label' => __('Knet')],
    ['value' => '2', 'label' => __('MPGS')]
  ];
 }
}