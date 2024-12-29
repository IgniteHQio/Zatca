<?php

namespace Malik12tree\ZATCA\Invoice\Enums;

use Malik12tree\ZATCA\Utils\Enum7;

class InvoiceVATReasonCode extends Enum7
{
    /** Financial services mentioned in Article 29 of the VAT Regulations */
    public const E_29 = 'VATEX-SA-29';

    /** Life insurance services mentioned in Article 29 of the VAT */
    public const E_29_7 = 'VATEX-SA-29-7';

    /** Real estate transactions mentioned in Article 30 of the VAT */
    public const E_30 = 'VATEX-SA-30';

    /** Export of goods */
    public const Z_32 = 'VATEX-SA-32';

    /** Export of services */
    public const Z_33 = 'VATEX-SA-33';

    /** The international transport of Goods  */
    public const Z_34_1 = 'VATEX-SA-34-1';

    /** The international transport of passengers */
    public const Z_34_2 = 'VATEX-SA-34-2';

    /** Services directly connected and incidental to a Supply of international passenger transport */
    public const Z_34_3 = 'VATEX-SA-34-3';

    /** Supply of a qualifying means of transport */
    public const Z_34_4 = 'VATEX-SA-34-4';

    /** Any services relating to Goods or passenger transportation, as defined in article twenty five of these Regulations */
    public const Z_34_5 = 'VATEX-SA-34-5';

    /** Medicines and medical equipment */
    public const Z_35 = 'VATEX-SA-35';

    /** Qualifying metals */
    public const Z_36 = 'VATEX-SA-36';

    /** Private education to citizen */
    public const Z_EDU = 'VATEX-SA-EDU';

    /** Private healthcare to citizen */
    public const Z_HEA = 'VATEX-SA-HEA';
}
