<?php

use Malik12tree\ZATCA\Utils\Rendering\Template;

use function Malik12tree\ZATCA\Utils\getLineItemSubtotal;
use function Malik12tree\ZATCA\Utils\getLineItemTaxes;
use function Malik12tree\ZATCA\Utils\getLineItemTotal;
use function Malik12tree\ZATCA\Utils\getLineItemUnitPrice;
use function Malik12tree\ZATCA\Utils\getLineItemUnitSubtotal;
use function Malik12tree\ZATCA\Utils\getLineItemVATCategory;
use function Malik12tree\ZATCA\Utils\nonEmptyString;
use function Malik12tree\ZATCA\Utils\zatcaNumberFormatFree;
use function Malik12tree\ZATCA\Utils\zatcaNumberFormatLong;
use function Malik12tree\ZATCA\Utils\zatcaNumberFormatNoWarning;
use function Malik12tree\ZATCA\Utils\zatcaNumberFormatShort;

$totalTaxes = $invoice->computeTotalTaxes();
$totalSubtotal = $invoice->computeTotalSubtotal();

$taxTotalRender = Template::render('@simplified-tax-invoice/tax-total', [
    'invoice' => $invoice,
]);

?>
<?= '<?xml version="1.0" encoding="UTF-8"?>'; ?>

<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2">
    <ext:UBLExtensions>
%UBL_EXTENSIONS_STRING%
    </ext:UBLExtensions>
    <cbc:ProfileID>reporting:1.0</cbc:ProfileID>
    <cbc:ID><?= $SERIAL_NUMBER; ?></cbc:ID>
    <cbc:UUID><?= $EGS['uuid']; ?></cbc:UUID>
    <cbc:IssueDate><?= $ISSUE_DATE; ?></cbc:IssueDate>
    <cbc:IssueTime><?= $ISSUE_TIME; ?></cbc:IssueTime>
    <cbc:InvoiceTypeCode name="<?= $CODE; ?>"><?= $TYPE; ?></cbc:InvoiceTypeCode>
    <cbc:DocumentCurrencyCode>SAR</cbc:DocumentCurrencyCode>
    <cbc:TaxCurrencyCode>SAR</cbc:TaxCurrencyCode>
<?php if (isset($CANCELLATION)) { ?>
        <cac:BillingReference>
            <cac:InvoiceDocumentReference>
                <cbc:ID><?= $CANCELLATION['serial_number']; ?></cbc:ID>
            </cac:InvoiceDocumentReference>
        </cac:BillingReference>
<?php } ?>
    <cac:AdditionalDocumentReference>
        <cbc:ID>ICV</cbc:ID>
        <cbc:UUID><?= $COUNTER_NUMBER; ?></cbc:UUID>
    </cac:AdditionalDocumentReference>
    <cac:AdditionalDocumentReference>
        <cbc:ID>PIH</cbc:ID>
        <cac:Attachment>
            <cbc:EmbeddedDocumentBinaryObject mimeCode="text/plain"><?= $PREVIOUS_INVOICE_HASH; ?></cbc:EmbeddedDocumentBinaryObject>
        </cac:Attachment>
    </cac:AdditionalDocumentReference>
    <cac:AdditionalDocumentReference>
        <cbc:ID>QR</cbc:ID>
        <cac:Attachment>
            <cbc:EmbeddedDocumentBinaryObject mimeCode="text/plain">%QR_CODE_DATA%</cbc:EmbeddedDocumentBinaryObject>
        </cac:Attachment>
    </cac:AdditionalDocumentReference>
    <cac:Signature>
        <cbc:ID>urn:oasis:names:specification:ubl:signature:Invoice</cbc:ID>
        <cbc:SignatureMethod>urn:oasis:names:specification:ubl:dsig:enveloped:xades</cbc:SignatureMethod>
    </cac:Signature>
    <cac:AccountingSupplierParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID="CRN"><?= $EGS['crn_number']; ?></cbc:ID>
            </cac:PartyIdentification>
            <cac:PostalAddress>
<?php if (nonEmptyString($EGS['location']['street'])) { ?>
                <cbc:StreetName><?= $EGS['location']['street']; ?></cbc:StreetName>
<?php } ?>
<?php if (nonEmptyString($EGS['location']['building'])) { ?>
                <cbc:BuildingNumber><?= $EGS['location']['building']; ?></cbc:BuildingNumber>
<?php } ?>
<?php if (nonEmptyString($EGS['location']['plot_identification'])) { ?>
                <cbc:PlotIdentification><?= $EGS['location']['plot_identification']; ?></cbc:PlotIdentification>
<?php } ?>
<?php if (nonEmptyString($EGS['location']['city_subdivision'])) { ?>
                <cbc:CitySubdivisionName><?= $EGS['location']['city_subdivision']; ?></cbc:CitySubdivisionName>
<?php } ?>
<?php if (nonEmptyString($EGS['location']['city'])) { ?>
                <cbc:CityName><?= $EGS['location']['city']; ?></cbc:CityName>
<?php } ?>
<?php if (nonEmptyString($EGS['location']['postal_zone'])) { ?>
                <cbc:PostalZone><?= $EGS['location']['postal_zone']; ?></cbc:PostalZone>
<?php } ?>
                <cac:Country>
                    <cbc:IdentificationCode>SA</cbc:IdentificationCode>
                </cac:Country>
            </cac:PostalAddress>
            <cac:PartyTaxScheme>
                <cbc:CompanyID><?= $EGS['vat_number']; ?></cbc:CompanyID>
                <cac:TaxScheme>
                    <cbc:ID>VAT</cbc:ID>
                </cac:TaxScheme>
            </cac:PartyTaxScheme>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName><?= $EGS['vat_name']; ?></cbc:RegistrationName>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingSupplierParty>
    <cac:AccountingCustomerParty>
<?php if (isset($CUSTOMER_INFO)) { ?>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID="CRN"><?= isset($CUSTOMER_INFO['crn_number']) ? $CUSTOMER_INFO['crn_number'] : ''; ?></cbc:ID>
            </cac:PartyIdentification>
            <cac:PostalAddress>
<?php if (nonEmptyString($CUSTOMER_INFO['street'])) { ?>
                <cbc:StreetName><?= $CUSTOMER_INFO['street']; ?></cbc:StreetName>
<?php } ?>
<?php if (nonEmptyString($CUSTOMER_INFO['building'])) { ?>
                <cbc:BuildingNumber><?= $CUSTOMER_INFO['building']; ?></cbc:BuildingNumber>
<?php } ?>
<?php if (nonEmptyString($CUSTOMER_INFO['plot_identification'])) { ?>
                <cbc:PlotIdentification><?= $CUSTOMER_INFO['plot_identification']; ?></cbc:PlotIdentification>
<?php } ?>
<?php if (nonEmptyString($CUSTOMER_INFO['city_subdivision'])) { ?>
                <cbc:CitySubdivisionName><?= $CUSTOMER_INFO['city_subdivision']; ?></cbc:CitySubdivisionName>
<?php } ?>
<?php if (nonEmptyString($CUSTOMER_INFO['city'])) { ?>
                <cbc:CityName><?= $CUSTOMER_INFO['city']; ?></cbc:CityName>
<?php } ?>
<?php if (nonEmptyString($CUSTOMER_INFO['postal_zone'])) { ?>
                <cbc:PostalZone><?= $CUSTOMER_INFO['postal_zone']; ?></cbc:PostalZone>
<?php } ?>
                <cac:Country>
                    <cbc:IdentificationCode>SA</cbc:IdentificationCode>
                </cac:Country>
            </cac:PostalAddress>
<?php if (nonEmptyString($CUSTOMER_INFO['vat_number'])) { ?>
            <cac:PartyTaxScheme>
                <cbc:CompanyID><?= $CUSTOMER_INFO['vat_number']; ?></cbc:CompanyID>
                <cac:TaxScheme>
                    <cbc:ID>VAT</cbc:ID>
                </cac:TaxScheme>
            </cac:PartyTaxScheme>
<?php } ?>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName><?= $CUSTOMER_INFO['buyer_name'] ?? ''; ?></cbc:RegistrationName>
            </cac:PartyLegalEntity>
        </cac:Party>
<?php } ?>
    </cac:AccountingCustomerParty>
<?php if (isset($ACTUAL_DELIVERY_DATE)) { ?>
    <cac:Delivery>
        <cbc:ActualDeliveryDate><?= $ACTUAL_DELIVERY_DATE; ?></cbc:ActualDeliveryDate>
<?php if (isset($LATEST_DELIVERY_DATE)) { ?>
        <cbc:LatestDeliveryDate><?= $LATEST_DELIVERY_DATE; ?></cbc:LatestDeliveryDate>
<?php } ?>
    </cac:Delivery>
<?php } ?>
<?php if (!isset($CANCELLATION)) { ?>
<?php if (isset($PAYMENT_METHOD)) { ?>
    <cac:PaymentMeans>
        <cbc:PaymentMeansCode><?= $PAYMENT_METHOD; ?></cbc:PaymentMeansCode>
    </cac:PaymentMeans>
<?php } ?>
<?php } elseif (381 == $TYPE || 383 == $TYPE) { ?>
    <cac:PaymentMeans>
        <cbc:PaymentMeansCode><?= $CANCELLATION['payment_method']; ?></cbc:PaymentMeansCode>
        <cbc:InstructionNote><?= $CANCELLATION['reason'] ?? 'No note Specified'; ?></cbc:InstructionNote>
    </cac:PaymentMeans>
<?php } ?>
<?= $taxTotalRender; ?>

    <cac:LegalMonetaryTotal>
        <cbc:LineExtensionAmount currencyID="SAR"><?= zatcaNumberFormatFree($totalSubtotal); ?></cbc:LineExtensionAmount>
        <cbc:TaxExclusiveAmount currencyID="SAR"><?= zatcaNumberFormatFree($totalSubtotal); ?></cbc:TaxExclusiveAmount>
        <cbc:TaxInclusiveAmount currencyID="SAR"><?= $total = zatcaNumberFormatFree($totalSubtotal + $totalTaxes); ?></cbc:TaxInclusiveAmount>
        <cbc:PrepaidAmount currencyID="SAR"><?= 0; ?></cbc:PrepaidAmount>
        <cbc:PayableAmount currencyID="SAR"><?= zatcaNumberFormatFree($totalSubtotal + $totalTaxes); ?></cbc:PayableAmount>
    </cac:LegalMonetaryTotal>
<?php foreach ($LINE_ITEMS as $lineItem) {
    ?>
    <cac:InvoiceLine>
        <cbc:ID><?= $lineItem['id']; ?></cbc:ID>
        <cbc:InvoicedQuantity unitCode="PCE"><?= zatcaNumberFormatFree($lineItem['quantity']); ?></cbc:InvoicedQuantity>
        <cbc:LineExtensionAmount currencyID="SAR"><?= zatcaNumberFormatFree(getLineItemSubtotal($lineItem)); ?></cbc:LineExtensionAmount>
        <cac:TaxTotal>
            <cbc:TaxAmount currencyID="SAR"><?= zatcaNumberFormatFree(getLineItemTaxes($lineItem)); ?></cbc:TaxAmount>
            <cbc:RoundingAmount currencyID="SAR"><?= zatcaNumberFormatFree(getLineItemTotal($lineItem)); ?></cbc:RoundingAmount>
        </cac:TaxTotal>
        <cac:Item>
            <cbc:Name><?= $lineItem['name']; ?></cbc:Name>
            <cac:ClassifiedTaxCategory>
                <cbc:ID><?= getLineItemVATCategory($lineItem)['category']; ?></cbc:ID>
                <cbc:Percent><?= zatcaNumberFormatFree($lineItem['vat_percent'] * 100); ?></cbc:Percent>
                <cac:TaxScheme>
                    <cbc:ID>VAT</cbc:ID>
                </cac:TaxScheme>
            </cac:ClassifiedTaxCategory>
        </cac:Item>
        <cac:Price>
            <cbc:PriceAmount currencyID="SAR"><?= zatcaNumberFormatFree(getLineItemUnitSubtotal($lineItem)); ?></cbc:PriceAmount>
<?php foreach ($lineItem['discounts'] ?? [] as $discount) { ?>
                <cac:AllowanceCharge>
                    <cbc:ChargeIndicator>false</cbc:ChargeIndicator>
                    <cbc:AllowanceChargeReason><?= $discount['reason']; ?></cbc:AllowanceChargeReason>
                    <cbc:Amount currencyID="SAR"><?= zatcaNumberFormatFree($discount['amount']); ?></cbc:Amount>
                    <cbc:BaseAmount currencyID="SAR"><?= zatcaNumberFormatFree(getLineItemUnitPrice($lineItem)); ?></cbc:BaseAmount>
                </cac:AllowanceCharge>
<?php } ?>
        </cac:Price>
    </cac:InvoiceLine>
<?php } ?>
</Invoice>
<?php return ['total' => $total, 'totalTax' => $totalTaxes]; ?>