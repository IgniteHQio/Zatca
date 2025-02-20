<?php

namespace Malik12tree\ZATCA\Invoice;

use Mpdf\Mpdf;

class SignedPDFAInvoice
{
    /** @var SignedInvoice */
    protected $signedInvoice;

    /** @var string */
    protected $pdf;

    public function __construct($signedInvoice, $options = [])
    {
        $this->signedInvoice = $signedInvoice;

        list($render, $resultOptions) = $this->signedInvoice->toHTML($options, true);

        $mpdf = new Mpdf($resultOptions['mpdf'] + [
            'PDFA' => true,
            'PDFAauto' => true,
            'mode' => 'utf-8',
            'tempDir' => sys_get_temp_dir(),
            'PDFAversion' => 3
        ]);
        // Define PDF/A-3A metadata
        $mpdf->SetMetadata([
            'pdfaid:part' => '3',
            'pdfaid:conformance' => 'A' // Set to 'A' for full compliance
        ]);
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->SetDefaultFontSize(7);
        $mpdf->simpleTables = true;
        $mpdf->keep_table_proportions = true;
        $mpdf->packTableData = true;
        $mpdf->shrink_tables_to_fit = 1;

        if ($resultOptions['hasLogo']) {
            $mpdf->imageVars['logo'] = file_get_contents($options['logo']);
        }

        $mpdf->WriteHTML($render);

        $tmpXml = tmpfile();
        //fwrite($tmpXml, $this->signedInvoice->getSignedInvoiceXML());
        fwrite($tmpXml, $options['encodedxml']);
        

        // Enable structure for PDF/A-3A
        // $mpdf->SetTitle('Invoice');
        // $mpdf->SetAuthor('Ignite');
        // $mpdf->SetLanguage('en');
        // $mpdf->SetDisplayMode('fullpage');

        $mpdf->SetAssociatedFiles([[
            'name' => $this->getInvoice()->attachmentName('xml'),
            'mime' => 'text/xml',
            'description' => '',
            'AFRelationship' => 'Alternative',
            'path' => stream_get_meta_data($tmpXml)['uri'],
        ]]);

        $data = $mpdf->OutputBinaryData();
        fclose($tmpXml);

        $this->pdf = $data;
    }

    public function getSignedInvoice()
    {
        return $this->signedInvoice;
    }

    public function getInvoice()
    {
        return $this->signedInvoice->getInvoice();
    }

    public function getPDF()
    {
        return $this->pdf;
    }

    public function saveAt($directoryPath)
    {
        $filePath = $directoryPath.DIRECTORY_SEPARATOR.$this->getInvoice()->attachmentName('pdf');
        if (!file_exists($directoryPath)) {
            mkdir($directoryPath, 0777, true);
        }
        file_put_contents($filePath, $this->pdf);
    }
}
