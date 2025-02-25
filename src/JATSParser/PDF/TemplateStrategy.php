<?php

use JATSParser\PDF\PDFConfig\Configuration;
use JATSParser\PDF\TCPDFDocument;

require_once __DIR__ . '/TemplateOne.php';
require_once __DIR__ . '/PDFConfig/Configuration.php';

class TemplateStrategy {

    private $pdfDocument;

    function __construct(string $templateName, $config) {
        $this->pdfDocument = new $templateName($config);
    }

    public function OutputPdf(){
        return $this->pdfDocument->Output('article.pdf', 'S');
    }

}