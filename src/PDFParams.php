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

use EdituraEDU\UBLRenderer\UBLObjectDefinitions\ParsedUBLInvoice;

class PDFParams
{
    public string $Title;
    public string $Author;
    public string $Creator;
    public string $Subject;
    public string $Keywords;

    public bool $IncludeOriginalXML=true;
    public bool $IncludeSignature=true;

    public string $FooterText="Pagina {PAGENO} din {nbpg}";

    public function __construct(string $Title="", string $Author="",
                                string $Creator="", string $Subject="",
                                string $Keywords="", bool $IncludeOriginalXML=true,
                                bool $IncludeSignature=true)
    {
        $this->Title = $Title;
        $this->Author = $Author;
        $this->Creator = $Creator;
        $this->Subject = $Subject;
        $this->Keywords = $Keywords;
        $this->IncludeOriginalXML = $IncludeOriginalXML;
        $this->IncludeSignature = $IncludeSignature;
    }

    public static function FromInvoice(ParsedUBLInvoice $invoice) : PDFParams
    {
        $params = new PDFParams();
        $params->Title = "Fact. ".$invoice->ID;
        $params->Author=$invoice->AccountingSupplierParty->GetName();
        $params->Creator=$invoice->AccountingSupplierParty->GetName();
        $params->Subject="Factura electronica";
        $params->Keywords="Factura electronica, ".$invoice->ID;
        return $params;
    }
}