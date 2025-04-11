<?php namespace JATSParser\PDF;

class TemplateStrategy {

    private $pdfDocument;

    function __construct(string $templateName, $config) {
        $namespace = "JATSParser\\PDF\\Templates\\$templateName\\$templateName"; //search for the class $templateName in the specified namespace 
        $this->pdfDocument = new $namespace($config);
    }

    public function OutputPdf(){
        return $this->pdfDocument->Output('article.pdf', 'S');
    }

}