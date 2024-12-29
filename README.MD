<h1 align="center">
  <code>malik12tree/zatca</code>
  <br/>
  <!-- ZATCA Phase 2 -->
  <img src="https://img.shields.io/badge/💸_ZATCA-Phase_2-00b694" />
  <!-- E-Invoicing -->
  <img src="https://img.shields.io/badge/📃_ZATCA-E--Invoicing-fc5f08">
  <!-- Encrypted -->
  <img src="https://img.shields.io/badge/🔐_ZATCA-Encrypted-f75394">
  <br/>
  <!-- XML Complaint -->
  <img src="https://img.shields.io/badge/📝_XML-Complaint-ff5555">
  <!-- PDF/A-3 Complaint -->
  <img src="https://img.shields.io/badge/📕_PDF%2FA--3-Complaint-ff5555">
  <!-- QR Code -->
  <img src="https://img.shields.io/badge/🔋_QR_Codes-Included-5793ff">
  <br/>
  <img src="https://img.shields.io/badge/php-7.4-777BB4?logo=php" />
  <img src="https://img.shields.io/github/license/Malik12tree/zatca?color=orange">
  <img src="https://img.shields.io/badge/version-0.1.0-blue.svg" />
</h1>

PHP implementation of ZATCA E-Invoicing standards.

## Table of Contents

- [Installation](#installation)
- [Glossary](#glossary)
- [Usage](#usage)
  - [Register EGS](#register-egs)
  - [Saving/Loading EGS](#savingloading-egs)
  - [Invoicing](#invoicing)
  - [PDF/A-3 Invoice](#pdfa-3-invoice)
- [License](#license)
- [Acknowledgments](#acknowledgments)
- [Disclaimer](#disclaimer)
- [Non-Affiliation Disclaimer](#non-affiliation-disclaimer)

## Installation

### Composer

```bash
composer require malik12tree/zatca
```

## Glossary

| Term | Definition                      |
| ---- | ------------------------------- |
| EGS  | E-Invoicing Generation Solution |

## Usage

### Register EGS

```php
use Malik12tree\ZATCA;
use Malik12tree\ZATCA\EGS;
use Malik12tree\ZATCA\Utils\Encoding\Crypto;

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
EGS::setEnv('sandbox');

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
    'uuid' => '00000000-0000-0000-0000-000000000000',

    'common_name' => 'Cashier #1',
    'model' => 'Windows Vista',

    // Known as CRN Number, License Number or Contract Number
    'crn_number' => '1234567890',
    // Known as VAT Name or Taxpayer Name
    'vat_name' => 'أسم الشركة',
    // Known as VAT Registration Number
    // Should be a valid 15 digits number starting and ending with "3"
    'vat_number' => '300001234500003',
    'branch_name' => 'اسم الفرع',
    'branch_industry' => 'الصناعة',

    'location' => [
        // https://splonline.com.sa/en/national-address-1/ for more info
        // Make sure the data is in Arabic
        'building' => '1234',
        'street' => 'شارع الملك فهد',
        'city_subdivision' => 'العلايا',
        'city' => 'الرياض',
        'plot_identification' => '0000',
        'postal_zone' => '00000',
    ],
]);

// Obtain an OTP (One-Time Password) from the Fatoora portal https://fatoora.zatca.gov.sa/onboard-solution for each EGS registration.
$otp = '123345';
$solutionName = 'My POS System\'s Name';

try {
    $egs->register($solutionName, $otp);
} catch (ZATCA\Exceptions\APIException $e) {
    // Handle API Exceptions gracefully
    // If you believe a bug occurred, please report it at https://github.com/Malik12tree/zatca/issues
    // $e->getCode()
    // $e->getMessage()
    // $e->getResponse()
    throw $e;
}

// ...
```

### Saving/Loading EGS

```php
use Malik12tree\ZATCA\EGSDatabases\EGSFileDatabase;

// Store the EGS object and its credentials for later use.
//  Be mindful to locate the EGS database to a safe place

$database = new EGSFileDatabase(__DIR__.'/private-secure-storage/solutions');

// Option 1
$database->save($egs);
// Option 2
$egs->setDatabase($database);
$egs->save();

// Retrieve a previously saved EGS using its UUID
$loadedEgs = $database->load('00000000-0000-0000-0000-000000000000');

// ...
```

### Invoicing

```php
use Malik12tree\ZATCA\Invoice;
use Malik12tree\ZATCA\Invoice\Enums\InvoiceCode;
use Malik12tree\ZATCA\Invoice\Enums\InvoiceType;

$invoice = $egs->invoice([
    'code' => InvoiceCode::TAX,
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
            'id' => "sugarcane_juicer_metal",
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
    $response = $egs->checkInvoiceCompliance($signedInvoice);

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
```

### PDF/A-3 Invoice

ZATCA rules that each invoice must be converted into a
standardized PDF/A-3 format containing all essential information.
Additionally, a signed XML invoice attachment must be included within the PDF.

Fortunately, this library simplifies this process significantly by utilizing a built-in PDF builder.

```php

$pdfInvoice = $signedInvoice->toPDF([
  // Optional Logo
  "logo" => __DIR__ . "/assets"
]);

// Save at directory.
// saveAt(...) automatically appends the PDF name according to
// ZATCA's naming convention.
$pdfInvoice->saveAt(__DIR__ . "/private-secure-storage/invoices");

// Alternatively, You can store the binary data.
$binaryData = $pdfInvoice->getPDF();
```

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details

## Acknowledgments

Special thanks to [zatca-xml-js](https://github.com/Repzo/zatca-xml-js).

## Disclaimer

This library is developed by humans and may contain errors or inaccuracies, despite its purpose of facilitating government invoicing processes. While we strive to ensure the highest quality and accuracy, we cannot guarantee the absolute correctness of all information and functionalities.

**Please note that we are not liable for any consequences arising from the use of this library, including but not limited to financial losses, legal issues, or system failures.**

It is strongly recommended to thoroughly test and validate all outputs generated by this library before relying on them for critical business decisions or government submissions.

## Non-Affiliation Disclaimer

This library is not affiliated with ZATCA or any of its subsidiaries or affiliates. The library's development and maintenance are independent of ZATCA. Any references to ZATCA or its standards are for informational purposes only and do not imply endorsement or sponsorship by ZATCA.
