<?php

namespace Malik12tree\ZATCA;

use Malik12tree\ZATCA\Invoice\SignedInvoice;
use Malik12tree\ZATCA\Utils\Encoding\Crypto;
use Malik12tree\ZATCA\Utils\Encoding\TLV;
use Malik12tree\ZATCA\Utils\Rendering\Template;
use Malik12tree\ZATCA\Utils\Validation;

use function Malik12tree\ZATCA\Utils\getLineItemDiscount;
use function Malik12tree\ZATCA\Utils\getLineItemPrice;
use function Malik12tree\ZATCA\Utils\getLineItemSubtotal;
use function Malik12tree\ZATCA\Utils\getLineItemTaxes;
use function Malik12tree\ZATCA\Utils\nonEmptyString;

class Invoice
{
    // BR-KSA-26
    public const INITIAL_PREVIOUS_HASH = 'NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==';
    private $xml;

    private $egsUnit;
    private $issueDate;
    private $issueTime;
    private $vatNumber;
    private $vatName;
    private $deliveryDate;
    private $serialNumber;
    private $type;
    private $code;
    private $customerInfo;
    private $lineItems;

    private $total;
    private $totalTax;

    private $cachedHash;

    public function __construct($unit, $data)
    {
    //    Validation::invoice($data);

        $this->egsUnit = $unit;
        $this->issueDate = $data['issue_date'];
        $this->issueTime = $data['issue_time'];
        $this->type = $data['type'];
        $this->code = $data['code'];
        $this->serialNumber = $data['serial_number'];
        $this->vatNumber = $unit['vat_number'];
        $this->vatName = $unit['vat_name'];
        $this->deliveryDate = $data['actual_delivery_date'] ?? null;
        $this->customerInfo = $data['customer_info'] ?? null;
        $this->lineItems = $data['line_items'] ?? [];

        list($this->xml, list(
            'total' => $this->total,
            'totalTax' => $this->totalTax,
        )) =
            Template::render('simplified-tax-invoice', [
                'invoice' => $this,
                'EGS' => $unit,
                'CUSTOMER_INFO' => $this->customerInfo,
                'LINE_ITEMS' => $data['line_items'] ?? [],
                'SERIAL_NUMBER' => $data['serial_number'],
                'ISSUE_DATE' => $data['issue_date'],
                'ISSUE_TIME' => $data['issue_time'],
                'CODE' => $data['code'],
                'TYPE' => $data['type'],
                'COUNTER_NUMBER' => $data['counter_number'],
                'PREVIOUS_INVOICE_HASH' => $data['previous_invoice_hash'],
                'CANCELLATION' => isset($data['cancellation'])
                    ? $data['cancellation']
                    : null,
                'ACTUAL_DELIVERY_DATE' => nonEmptyString($data['actual_delivery_date']) ? $data['actual_delivery_date'] : null,
                'LATEST_DELIVERY_DATE' => nonEmptyString($data['latest_delivery_date']) ? $data['latest_delivery_date'] : null,
                'PAYMENT_METHOD' => nonEmptyString($data['payment_method']) ? $data['payment_method'] : null,
            ], true);
        $this->xml = str_replace("\r\n", "\n", $this->xml);
    }

    public function getVATNumber()
    {
        return $this->vatNumber;
    }

    public function getVATName()
    {
        return $this->vatName;
    }

    public function getDeliveryDate()
    {
        return $this->deliveryDate;
    }

    public function getSerialNumber()
    {
        return $this->serialNumber;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function getIssueDate()
    {
        return $this->issueDate;
    }

    public function getIssueTime()
    {
        return $this->issueTime;
    }

    public function getEGS()
    {
        return $this->egsUnit;
    }

    public function getLineItems()
    {
        return $this->lineItems;
    }

    public function getCustomerInfo($key = null)
    {
        return $key ? ($this->customerInfo[$key] ?? null) : $this->customerInfo;
    }

    public function getFormattedIssueDate()
    {
        return "{$this->issueDate} {$this->issueTime}";
    }

    public function computeTotalTaxes()
    {
        $total = 0;
        foreach ($this->lineItems as $lineItem) {
            $total += getLineItemTaxes($lineItem);
        }

        return $total;
    }

    public function computeTotalDiscounts()
    {
        $total = 0;
        foreach ($this->lineItems as $lineItem) {
            $total += getLineItemDiscount($lineItem);
        }

        return $total;
    }

    public function computeTotalSubtotal()
    {
        $total = 0;
        foreach ($this->lineItems as $lineItem) {
            $total += getLineItemSubtotal($lineItem);
        }

        return $total;
    }

    public function computeTotalPrice()
    {
        $total = 0;
        foreach ($this->lineItems as $lineItem) {
            $total += getLineItemPrice($lineItem);
        }

        return $total;
    }

    public function computeTotal()
    {
        return $this->computeTotalSubtotal() + $this->computeTotalTaxes();
    }

    public function attachmentName($extension = '')
    {
        $name = "{$this->vatNumber}_".date('Ymd\THis', strtotime("{$this->issueDate} {$this->issueTime}"))."_{$this->serialNumber}";
        if ($extension) {
            $name .= ".{$extension}";
        }

        return $name;
    }

    public function hash()
    {
        if ($this->cachedHash) {
            return $this->cachedHash;
        }

        $cleanInvoice = $this->cleanedXML();

        $hash = Crypto::hashSHA256($cleanInvoice);
        $this->cachedHash = base64_encode($hash);

        return $this->cachedHash;
    }

    public function sign($certificate, $privateKey)
    {
        $invoiceHash = $this->hash();
        $certificateInfo = Crypto::getCertificateInfo($certificate);

        $digitalSignature = base64_encode(Crypto::signSHA256($invoiceHash, $privateKey));

        $qr = $this->qr($digitalSignature, $certificateInfo['publicKey'], $certificateInfo['signature']);

    

        $ublPropertiesVariables = [
            'SIGN_TIMESTAMP' => date('Y-m-d\TH:i:s\Z'),
            'CERTIFICATE_HASH' => $certificateInfo['hash'],
            'CERTIFICATE_ISSUER' => $certificateInfo['issuer'],
            'CERTIFICATE_SERIAL_NUMBER' => $certificateInfo['serialNumber'],
            
        ];

        $ublSignaturePropertiesRender =
            Template::render('@simplified-tax-invoice/ubl-signature/properties', $ublPropertiesVariables);
        $ublSignaturePropertiesRenderForSigning =
            Template::render('@simplified-tax-invoice/ubl-signature/properties/for-signing', $ublPropertiesVariables);

        $ublSignaturePropertiesRenderForSigning = str_replace("\r\n", "\n", $ublSignaturePropertiesRenderForSigning);
        $ublSignaturePropertiesRender = str_replace("\r\n", "\n", $ublSignaturePropertiesRender);

        $signedUBLSignaturePropertiesRender = base64_encode(bin2hex(Crypto::hashSHA256($ublSignaturePropertiesRenderForSigning)));

        $ublSignatureRender = Template::render('@simplified-tax-invoice/ubl-signature', [
            'INVOICE_HASH' => $invoiceHash,
            'SIGNED_PROPERTIES_HASH' => $signedUBLSignaturePropertiesRender,
            'DIGITAL_SIGNATURE' => $digitalSignature,
            'CERTIFICATE' => Crypto::cleanCertificate($certificate),
            'SIGNED_PROPERTIES_XML' => $ublSignaturePropertiesRender,
        ]);
        $ublSignatureRender = str_replace("\r\n", "\n", $ublSignatureRender);

        $invoiceRender = $this->xml;
        $invoiceRender = str_replace(
            ['%UBL_EXTENSIONS_STRING%', '%QR_CODE_DATA%'],
            [$ublSignatureRender, $qr],
            $invoiceRender
        );

        return new SignedInvoice($this, $invoiceRender, $invoiceHash, $qr);
    }

    private function qr($digitalSignature, $publicKey, $signature)
    {
        $sellerName = $this->vatName;
        $vatNumber = $this->vatNumber;
        $total = $this->total;
        $vatTotal = $this->totalTax;
        $dateTime = date('Y-m-d\TH:i:s', strtotime("{$this->issueDate} {$this->issueTime}"));

        $qrTLV = TLV::encodeAll([
            0x01 => $sellerName,
            0x02 => $vatNumber,
            0x03 => $dateTime,
            0x04 => $total,
            0x05 => $vatTotal,
            0x06 => $this->hash(),
            0x07 => $digitalSignature,
            0x08 => $publicKey,
            0x09 => $signature,
        ]);

        return base64_encode($qrTLV);
    }

    public function cleanedXML()
    {
        $document = new \DOMDocument();
        $document->loadXML($this->xml);

        $element = $document->getElementsByTagName('UBLExtensions')->item(0);
        if ($element) {
            $element->parentNode->removeChild($element);
        }

        $element = $document->getElementsByTagName('Signature')->item(0);
        if ($element) {
            $element->parentNode->removeChild($element);
        }

        // Remove QR Code Tag
        $element = $document->getElementsByTagName('AdditionalDocumentReference')->item(2);
        if ($element) {
            $element->parentNode->removeChild($element);
        }

        $cleanXML = str_replace(
            [
                "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n",
            ],
            [
                '',
            ],
            $document->saveXML(null, LIBXML_NOEMPTYTAG)
        );

        return trim($cleanXML);
    }
}
