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




// Your must set the EGS environment or else initiating an EGS will throw an exception.
// Possible values are sandbox, simulation and production.
// - sandbox:
//   - For testing purposes only.
//   - Validation only.
//   - No web-based portal.
//   - Known Fixed OTPs:
//      - Valid: 123345
//      - Invalid: 111111
//      - Expired: 222222
// - simulation:
//   - For testing purposes only.
//   - Connects to the Fatoora portal.
//   - Generate OTPs.
//   - Simulates real validation and storing of EGSs and invoices at the simulation database.
//   - Accessing Portal:
//      - Head to https://fatoora.zatca.gov.sa
//      - Login with your credentials.
//      - Click "Fatoora Simulation Portal".
// - production:
//   - For live production use.
//   - Exercise caution when using this environment.
//   - Connects to the Fatoora portal.
//   - Generate OTPs
//   - Real validation and storing of EGSs and invoices at the production database.
//   - Accessing Portal:
//      - Head to https://fatoora.zatca.gov.sa
//      - Login with your credentials.
//      - Click "Fatoora Simulation Portal".
//
// - simulation: This is for testing purposes containing real validation with a real database storing EGSs and invoices. It does connect to a portal. You can generate OTPs from the Fatoora portal. You can view generate EGSs and invoices at the portal.
// - production: This is used dangerously in production.
EGS::setEnv('production');

// Disabled by default.
// To prioritize code safety and prevent unexpected behavior, API warnings are disabled by default.
// When disabled, any warnings are converted into exceptions, forcing the code to halt and address the issue immediately.
// Enabling warnings can introduce potential risks,
// so it's recommended to keep them turned off unless absolutely necessary.
// However, take notes that even if warnings are returned, ZATCA may still accept the invoice.
EGS::allowWarnings(true);


$egs = new EGS([
    // EGS Serial Number
    // You should generate a unique UUID for each EGS
    // Use Crypto::uuid4() to generate a secure UUID
    'uuid' => 'a39bab9d-1e5a-4ae0-92a7-278b48714883',

    'common_name' => 'Ignite-UN-01',
    'model' => 'UN',

    // Known as CRN Number, License Number or Contract Number
    'crn_number' => '311413225100003',
    // Known as VAT Name or Taxpayer Name
    'vat_name' => 'AWABED Almanthar LLC',
    // Known as VAT Registration Number
    // Should be a valid 15 digits number starting and ending with "3"
    'vat_number' => '311413225100003',
    'branch_name' => 'UN',
    'branch_industry' => 'Retail',

    'location' => [
        // https://splonline.com.sa/en/national-address-1/ for more info
        // Make sure the data is in Arabic
        'building' => '6777',
        'street' => 'Riyadh, Nasar bin al dareef street',
        'city_subdivision' => 'Alaqeeq Dist',
        'city' => 'Riyadh',
        'plot_identification' => '3127',
        'postal_zone' => '13511',
    ],
]);



// Obtain an OTP (One-Time Password) from the Fatoora portal https://fatoora.zatca.gov.sa/onboard-solution for each EGS registration.
$otp = '644525';
$solutionName = 'IGNITE';

try {
    $egs->register($solutionName, $otp);
} catch (ZATCA\Exceptions\APIException $e) {
    // Handle API Exceptions gracefully
    // If you believe a bug occurred, please report it at https://github.com/Malik12tree/zatca/issues
    // $e->getCode()
    // $e->getMessage()
    // $e->getResponse()
    echo $e;
}

// ...

echo $egs;

$database = new EGSFileDatabase(__DIR__.'/private-secure-storage/solutions');

// Option 1
$database->save($egs);
// Option 2
$egs->setDatabase($database);
$egs->save();

// Retrieve a previously saved EGS using its UUID
//$loadedEgs = $database->load('00000000-0000-0000-0000-012345670000');

// ...


// $invoice = $egs->invoice([
//     'code' => InvoiceCode::SIMPLE,
//     'type' => InvoiceType::INVOICE,

//     'serial_number' => 'INV-0000001',
//     'counter_number' => 0,

//     'issue_date' => date('Y-m-d'),
//     'issue_time' => date('H:i:s'),
//     'actual_delivery_date' => date('Y-m-d', strtotime('tomorrow')),

//     'previous_invoice_hash' => Invoice::INITIAL_PREVIOUS_HASH,

//     'customer_info' => [
//         'crn_number' => '1234567890',
//         'vat_number' => '300000000000003',
//         'buyer_name' => 'محمود عغوة',

//         // https://splonline.com.sa/en/national-address-1/ for more info
//         // Make sure the data is in Arabic
//         'building' => '1234',
//         'street' => 'نفق العباسية',
//         'city_subdivision' => 'العلايا',
//         'city' => 'الدمام',
//         'plot_identification' => '0000',
//         'postal_zone' => '00000',
//     ],

//     'line_items' => [
//         [
//             'id' => "sugarcane_juicer_metal",
//             'name' => 'عصارة قصب سكر معدنية',
//             'quantity' => 1.0,
//             'unit_price' => 525.0,
//             'vat_percent' => 0.15,
//             'discounts' => [
//                 [
//                     'amount' => 100.0,
//                     'reason' => 'زبون مميز',
//                 ],
//             ],
//         ],
//     ],
// ]);

// try {
//     $signedInvoice = $egs->signInvoice($invoice);

//     // Throws an exception if the EGS is not compliant
//     // Even if the EGS is compliant, $response may contain warnings
//    // $response = $egs->checkInvoiceCompliance($signedInvoice);

//     $response = $egs->reportInvoice($signedInvoice);
// } catch (ZATCA\Exceptions\APIException $e) {
//     // Handle API Exceptions gracefully
//     // If you believe a bug occurred, please report it at https://github.com/Malik12tree/zatca/issues
//     // $e->getCode()
//     // $e->getMessage()
//     // $e->getResponse()
//     throw $e;
// } catch (Exception $e) {
//     // Handle Exceptions gracefully
//     // If you believe a bug occurred, please report it at https://github.com/Malik12tree/zatca/issues
//     throw $e;
// }

// // ...


// $pdfInvoice = $signedInvoice->toPDF([
//     // Optional Logo
//     "logo" => __DIR__ . "/assets"
//   ]);
  
//   // Save at directory.
//   // saveAt(...) automatically appends the PDF name according to
//   // ZATCA's naming convention.
//   $pdfInvoice->saveAt(__DIR__ . "/private-secure-storage/invoices");
  
//   // Alternatively, You can store the binary data.
//   $binaryData = $pdfInvoice->getPDF();