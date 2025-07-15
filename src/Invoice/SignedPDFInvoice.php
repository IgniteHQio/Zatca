<?php

namespace Malik12tree\ZATCA\Invoice;

use Mpdf\Mpdf;

class SignedPDFInvoice
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

            'tempDir' => sys_get_temp_dir(),
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
        fwrite($tmpXml, $this->signedInvoice->getSignedInvoiceXML());

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
        $fileName = $this->getInvoice()->attachmentName('pdf');
        $fileName = str_replace('/', '-', $fileName);

        $filePath = $directoryPath.DIRECTORY_SEPARATOR. $fileName;
        // if (!file_exists($directoryPath)) {
        //     mkdir($directoryPath, 0777, true);
        // }
        file_put_contents($filePath, $this->pdf);
    }
}
