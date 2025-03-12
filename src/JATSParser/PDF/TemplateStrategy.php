<?php namespace JATSParser\PDF;

use JATSParser\PDF\PDFConfig\Configuration;
use JATSParser\PDF\TCPDFDocument;
use JATSParser\PDF\TemplateOne;

require_once __DIR__ . '/Templates/TemplateOne.php';
require_once __DIR__ . '/PDFConfig/Configuration.php';

class TemplateStrategy {

    private $pdfDocument;

    function __construct(string $templateName, $config) {
        $namespace = "JATSParser\\PDF\\Templates\\$templateName";
        $this->pdfDocument = new $namespace($config);
    }

    public function OutputPdf(){
        return $this->pdfDocument->Output('article.pdf', 'S');
    }

}