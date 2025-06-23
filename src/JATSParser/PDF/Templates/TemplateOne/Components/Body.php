<?php namespace JATSParser\PDF\Templates\TemplateOne\Components;

use JATSParser\PDF\PDFBodyHelper;
use JATSParser\PDF\Templates\GenericComponent;

class Body extends GenericComponent{

    public function render(){
        $bodyFont = $this->config->getFontConfig('philosopher');
        $htmlString = $this->config->getMetadata('html_string');
        $pluginPath = $this->config->getMetadata('plugin_path');
        $leftMargin = $this->config->getMargin('body_left');

        $this->pdfTemplate->SetLeftMargin($leftMargin);
        $this->pdfTemplate->SetRightMargin($leftMargin);
        $this->pdfTemplate->SetFont($bodyFont['family'], $bodyFont['style'], $bodyFont['size']);

        $htmlString .= "\n" . '<style>' . "\n" . file_get_contents($pluginPath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'styles' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'pdfGalley.css') . '</style>';
        $htmlString = PDFBodyHelper::_prepareForPdfGalley($htmlString, $this->config);
        $this->pdfTemplate->writeHTML($htmlString, true, false, true, false, 'J');
    }
}