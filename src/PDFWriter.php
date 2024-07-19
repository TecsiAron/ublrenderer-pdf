<?php
/*
 *  Copyright [2024] [Tecsi Aron]
 *
 *     Licensed under the Apache License, Version 2.0 (the "License");
 *     you may not use this file except in compliance with the License.
 *     You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *     Unless required by applicable law or agreed to in writing, software
 *     distributed under the License is distributed on an "AS IS" BASIS,
 *     WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *     See the License for the specific language governing permissions and
 *     limitations under the License.
 */

namespace EdituraEDU\UBLRenderer\PDF;

use EdituraEDU\UBLRenderer\IInvoiceWriter;
use EdituraEDU\UBLRenderer\InvoiceWriter;
use EdituraEDU\UBLRenderer\UBLObjectDefinitions\ParsedUBLInvoice;
use EdituraEDU\UBLRenderer\UBLRendererWarning;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class PDFWriter extends InvoiceWriter
{

    private ?string $Path;
    private ?string $OriginalXML;
    private ?string $Signature;
    private PDFParams $Params;
    private array $Warnings = [];

    private bool $MemoryOnly;

    public ?string $LastPDF=null;

    /**
     * If path is null or is a directory InvoiceWriter::NormalizePath will be used to generate the path
     * @see InvoiceWriter::NormalizePath
     * @param string|null $path if null dirname(__FILE__)."/../output/<invoice_id>.pdf will be used"
     */
    public function __construct(PDFParams $params,  ?string $path, string $OriginalXML, ?string $signature = null, bool $memoryOnly=false)
    {
        $this->Params = $params;
        $this->Path = $path;
        $this->OriginalXML = $OriginalXML;
        $this->Signature = $signature;
        $this->MemoryOnly = $memoryOnly;
    }

    public function WriteContent(string $hmlContent, ParsedUBLInvoice $invoice): void
    {
        $this->Warnings=[];
        if(!$this->MemoryOnly)
        {
            $this->Path = $this->NormalizePath($this->Path, $invoice, "pdf");
        }
        $assocFiles=[];
        if($this->Params->IncludeOriginalXML)
        {
            $attachedFile = $this->GenerateIncludeFile($this->OriginalXML, 'original-ubl.xml');
            if($attachedFile!==false)
            {
                $assocFiles[]=$attachedFile;
            }
        }
        if($this->Params->IncludeSignature && $this->Signature!==null)
        {
            $attachedFile = $this->GenerateIncludeFile($this->Signature, 'signature.xml');
            if($attachedFile!==false)
            {
                $assocFiles[]=$attachedFile;
            }
        }
        $mpdf = new Mpdf();
        $mpdf->SetTitle($this->Params->Title);
        $mpdf->SetAuthor($this->Params->Author);
        $mpdf->SetCreator($this->Params->Creator);
        $mpdf->SetSubject($this->Params->Subject);
        $mpdf->SetKeywords($this->Params->Keywords);
        $mpdf->SetFooter($this->Params->FooterText);
        $mpdf->WriteHTML($hmlContent);
        if(count($assocFiles)>0)
        {
            $mpdf->SetAssociatedFiles($assocFiles);
        }
        $this->LastPDF=$mpdf->Output('', Destination::STRING_RETURN);
        $this->CleanAttachments($assocFiles);
        if($this->MemoryOnly)
        {
            return;
        }
        file_put_contents($this->Path, $this->LastPDF);
    }

    public function GenerateIncludeFile(string $content, string $name):array|false
    {
        try
        {
            $tmpDir = sys_get_temp_dir();
            $uuid = uniqid();
            $path = $tmpDir . DIRECTORY_SEPARATOR . $uuid;
            while (file_exists($path))
            {
                $uuid = uniqid();
                $path = $tmpDir . DIRECTORY_SEPARATOR . $uuid;
            }
            mkdir($path);
            $xmlFilePath = $tmpDir . DIRECTORY_SEPARATOR . $uuid . DIRECTORY_SEPARATOR . $name;
            file_put_contents($xmlFilePath, $content);
            return[
                'name' => $name,
                'mime' => 'text/xml',
                'description' => 'Signature',
                'AFRelationship' => 'Unspecified',
                'path' => $xmlFilePath
            ];
        }
        catch (\Exception $e)
        {
            error_log("Could not include original XML in PDF: " . $e->getMessage());
            $this->Warnings[] = new UBLRendererWarning("Could not include original XML in PDF: " . $e->getMessage(), "INC_FAIL_$name");
            return false;
        }
    }

    private function CleanAttachments(array $assocFiles):void
    {
        for($i=0; $i<sizeof($assocFiles); $i++)
        {
            @unlink($assocFiles[$i]['path']);
            @rmdir(dirname($assocFiles[$i]['path']));
        }
    }

    /**
     * @return UBLRendererWarning[]
     */
    public function GetWarnings(): array
    {
        return $this->Warnings;
    }
}