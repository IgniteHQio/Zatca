<?php

namespace Malik12tree\ZATCA\Invoice\Enums;

use Malik12tree\ZATCA\Utils\Enum7;

/**
 * 11.2.4 VAT categories code From
 * https://istitlaa.ncc.gov.sa/ar/finance/gazt/einvoicingimplementationresolution/Documents/ZATCA_Electronic_Invoice_XML_Implementation_Standard.pdf.
 */
class InvoiceVATCategory extends Enum7
{
    public const STANDARD = 'S';
    public const ZERO_RATED = 'Z';
    public const EXEMPT = 'E';
    public const OUT_OF_SCOPE = 'O';

    public static function isValidZeroValue($value)
    {
        return self::STANDARD !== $value && self::isValidValue($value);
    }
}
