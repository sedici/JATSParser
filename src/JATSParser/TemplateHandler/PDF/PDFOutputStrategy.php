<?php

namespace JATSParser\TemplateHandler\PDF;

use JATSParser\TemplateHandler\PDF\PDFCreationService;
use Mpdf\Mpdf;
use JATSParser\Body\Document;
use JATSParser\HTML\Document as HTMLDocument;
use APP\facades\Repo;
use JATSParser\TemplateHandler\OutputStrategy;

class PDFOutputStrategy implements OutputStrategy {

  public static function generateOutput($plugin, $fileMgr, $journalId, $localeKey, $fileId, $htmlString, $configuration, $metadata, $ojsConfiguration)
  {
    $selectedTemplate = $ojsConfiguration['selected_template'];

		if(!$selectedTemplate) {
			return; # se habló que si no se tiene una plantilla seleccionada no se debe emitir un PDF bajo ningún criterio, esto debería modificarse para que no genere un error
		}

		$privateTemplatesDir = $fileMgr->getBasePath() . "/journals/$journalId/jatsParser_templates";
		$publicTemplatesDir = $plugin->getPluginPath() . "/templates/SUMARC";

		$publicTemplateManager = new \Smarty(); # Con esta instancia se pueden settear las rutas que se desee de Smarty, así no usamos la global de OJS.
		$publicTemplateManager->setTemplateDir($publicTemplatesDir); # La ruta es .../jatsParser/templates

		$privateTemplateManager = new \Smarty();
		$privateTemplateManager->setTemplateDir($privateTemplatesDir);

		$pdfCreationService = new PDFCreationService($publicTemplateManager, $privateTemplateManager);

		$pdf = new Mpdf([ # Sacar los márgenes de la config de OJS (cuando exista)
			'mode' => 'utf-8',
			'PDFA' => true,
			'PDFAauto' => true,
			'margin_top' => $ojsConfiguration['margin_top'],
			'margin_bottom' => $ojsConfiguration['margin_bottom'], 
			'margin_left' => $ojsConfiguration['margin_left'] ?? 25,
			'margin_right' => $ojsConfiguration['margin_right'] ?? 25,
		]); # Versión 8.1.3. Los genero así para que la salida sea un PDF/A válido
		
		//$pdf->SetAnchor2Bookmark(1);

		$submissionFile = Repo::submissionFile()->get($fileId);
		$jatsDocument = new Document($fileMgr->getBasePath() . DIRECTORY_SEPARATOR . $submissionFile->getData('path'));
		$citeProc = new HTMLDocument($jatsDocument);
		$dom = new \DOMDocument('1.0', 'utf-8');
		$htmlHead = "<!DOCTYPE html><head><meta http-equiv='Content-Type' content='text/html'; charset=utf-8/></head>";
		$dom->loadHTML($htmlHead . $htmlString);
		$xpath = new \DOMXPath($dom);

		$citationStyle = $plugin->getCitationStyle(Repo::journal()->get($journalId));
		$citeProc->setReferences($citationStyle, $localeKey, false);

    return $pdfCreationService->buildPDF($pdf, $htmlString, $xpath, $dom, $citeProc, $configuration, $metadata, $selectedTemplate, $ojsConfiguration);
  }

}