<?php

namespace Malik12tree\ZATCA\Invoice\Enums;

use Malik12tree\ZATCA\Utils\Enum7;

class InvoicePaymentMethod extends Enum7
{
    public const CASH = '10';
    public const CREDIT = '30';
    public const BANK_ACCOUNT = '42';
    public const BANK_CARD = '48';
}
