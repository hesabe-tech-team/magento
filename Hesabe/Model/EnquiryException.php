<?php
declare(strict_types=1);

namespace HesabePayment\Hesabe\Model;

/**
 * Hesabe's Transaction Enquiry could not be asked, or its answer could not be read. Nothing
 * is known about the payment, so nothing may be changed.
 */
class EnquiryException extends \RuntimeException
{
}
