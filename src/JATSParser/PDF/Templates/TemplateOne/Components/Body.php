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

		$refs = [];

        $htmlString .= "\n" . '<style>' . "\n" . file_get_contents($pluginPath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'styles' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'pdfGalley.css') . '</style>';
        $htmlString = PDFBodyHelper::_prepareForPdfGalley($htmlString, $this->config, $this->pdfTemplate, $refs);

        $partes = preg_split('/({{LINK:ref\d+:[^}]+}})/', $htmlString, -1, PREG_SPLIT_DELIM_CAPTURE);

        error_log(print_r($refs, true));

        foreach ($partes as $parte) {
            if (preg_match('/{{LINK:(ref\d+):(.+?)}}/', $parte, $match)) {
                $refId = $match[1];
                $texto = $match[2];
                $this->pdfTemplate->Write(0, $texto, $refs[$refId], 0);
            } else {
                $this->pdfTemplate->writeHTML($parte, false, false, true, false, '');
            }
        }

        $this->pdfTemplate->AddPage(); // o saltos si están al final del artículo
        foreach ($refs as $refId => $linkId) {
            $this->pdfTemplate->SetLink($linkId); // marcar posición
            $this->pdfTemplate->Write(0, "[{$refId}] Texto real de la referencia", '', 1);
        }        

    }
}