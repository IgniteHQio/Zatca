<?php

use Malik12tree\ZATCA;
use Malik12tree\ZATCA\EGS;
use Malik12tree\ZATCA\Utils\Encoding\Crypto;
use Malik12tree\ZATCA\EGSDatabases\EGSFileDatabase;
use Malik12tree\ZATCA\Invoice;
use Malik12tree\ZATCA\Invoice\Enums\InvoiceCode;
use Malik12tree\ZATCA\Invoice\Enums\InvoiceType;

function dd(...$arg)
{
    echo '<pre>';
    print_r($arg);
    exit;
}
require __DIR__ . '/vendor/autoload.php';

const ROOT_PATH = __DIR__;

require ROOT_PATH . '/src/EGS.php';
require ROOT_PATH . '/src/EGSDatabase.php';
require ROOT_PATH . '/src/Invoice.php';

// - production: This is used dangerously in production.
EGS::setEnv('simulation');

EGS::allowWarnings(true);


$database = new EGSFileDatabase(__DIR__.'/private-secure-storage/solutions');


// Retrieve a previously saved EGS using its UUID
$loadedEgs = $database->load('00000000-0000-0000-0000-012345678000');



$egs = $loadedEgs;


$invoice = $egs->invoice([
    'code' => InvoiceCode::SIMPLE,
    'type' => InvoiceType::INVOICE,

    'serial_number' => 'INV-0000001',
    'counter_number' => 0,

    'issue_date' => date('Y-m-d'),
    'issue_time' => date('H:i:s'),
    'actual_delivery_date' => date('Y-m-d', strtotime('tomorrow')),

    'previous_invoice_hash' => Invoice::INITIAL_PREVIOUS_HASH,

    'customer_info' => [
        'crn_number' => '1234567890',
        'vat_number' => '300000000000003',
        'buyer_name' => 'محمود عغوة',

        // https://splonline.com.sa/en/national-address-1/ for more info
        // Make sure the data is in Arabic
        'building' => '1234',
        'street' => 'نفق العباسية',
        'city_subdivision' => 'العلايا',
        'city' => 'الدمام',
        'plot_identification' => '0000',
        'postal_zone' => '00000',
    ],

    'line_items' => [
        [
            'id' => 101,
            'name' => 'عصارة قصب سكر معدنية',
            'quantity' => 1.0,
            'unit_price' => 525.0,
            'vat_percent' => 0.15,
            'discounts' => [
                [
                    'amount' => 100.0,
                    'reason' => 'زبون مميز',
                ],
            ],
        ],
    ],
]);

try {
    $signedInvoice = $egs->signInvoice($invoice);

    // Throws an exception if the EGS is not compliant
    // Even if the EGS is compliant, $response may contain warnings
  //  $response = $egs->checkInvoiceCompliance($signedInvoice);

    $response = $egs->reportInvoice($signedInvoice);
} catch (ZATCA\Exceptions\APIException $e) {
    // Handle API Exceptions gracefully
    // If you believe a bug occurred, please report it at https://github.com/Malik12tree/zatca/issues
    // $e->getCode()
    // $e->getMessage()
    // $e->getResponse()
    throw $e;
} catch (Exception $e) {
    // Handle Exceptions gracefully
    // If you believe a bug occurred, please report it at https://github.com/Malik12tree/zatca/issues
    throw $e;
}

// ...