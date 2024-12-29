<?php

namespace Malik12tree\ZATCA\Invoice\Enums;

use Malik12tree\ZATCA\Utils\Enum7;

class InvoiceCode extends Enum7
{
    // TODO: BR-KSA-06
    // P (position 3) = 3rd Party invoice transaction, 0 for false, 1 for true
    // N (position 4) = Nominal invoice transaction, 0 for false, 1 for true
    // E (position 5) = Exports invoice transaction, 0 for false, 1 for true
    // S (position 6) = Summary invoice transaction, 0 for false, 1 for true
    // B (position 7) = Self billed invoice
    public const TAX = '0100000';
    public const SIMPLE = '0200000';
}
