<?php namespace JATSParser\PDF\Templates\TemplateOne\Components;

use JATSParser\PDF\PDFBodyHelper;
use JATSParser\PDF\Templates\GenericComponent;

class Body extends GenericComponent{

    public function render(){
		$bodyConfig = $this->config->getBodyConfig();
		$htmlString = $this->config->getHtmlString();
		$pluginPath = $this->config->getPluginPath();

		$this->pdfTemplate->SetLeftMargin(27);
		$this->pdfTemplate->SetRightMargin(27);

		$this->pdfTemplate->SetFont($bodyConfig['config']['font']['family'], $bodyConfig['config']['font']['style'], $bodyConfig['config']['font']['size']);

		$htmlString .= "\n" . '<style>' . "\n" . file_get_contents($pluginPath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'styles' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'pdfGalley.css') . '</style>';
		$htmlString = PDFBodyHelper::_prepareForPdfGalley($htmlString, $this->config);
		error_log($htmlString);
		$this->pdfTemplate->writeHTML($htmlString, true, false, true, false, 'J');
	}
}