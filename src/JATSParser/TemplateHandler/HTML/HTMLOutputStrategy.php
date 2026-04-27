<?php

namespace JATSParser\TemplateHandler\HTML;

use JATSParser\TemplateHandler\HTML\HTMLCreationService;
use Mpdf\Mpdf;
use JATSParser\Body\Document;
use JATSParser\HTML\Document as HTMLDocument;
use APP\facades\Repo;
use JATSParser\TemplateHandler\OutputStrategy;

class HTMLOutputStrategy implements OutputStrategy {

  public static function generateOutput($plugin, $fileMgr, $journalId, $localeKey, $fileId, $htmlString, $configuration, $metadata, $ojsConfiguration)
  {
    $selectedTemplate = $ojsConfiguration['selected_template'];

		if(!$selectedTemplate) {
			return; # se habló que si no se tiene una plantilla seleccionada no se debe emitir un PDF bajo ningún criterio, esto debería modificarse para que no genere un error
		}

		$privateTemplatesDir = $fileMgr->getBasePath() . "/journals/$journalId/jatsParser_templates";
		$publicTemplatesDir = $plugin->getPluginPath() . "/templates/SUMARC";

		$cacheDir = \TemplateManager::getManager()->compile_dir;

		$publicTemplateManager = new \Smarty(); # Con esta instancia se pueden settear las rutas que se desee de Smarty, así no usamos la global de OJS.
		$publicTemplateManager->setTemplateDir($publicTemplatesDir); # La ruta es .../jatsParser/templates
		$publicTemplateManager->setCompileDir($cacheDir);

		$privateTemplateManager = new \Smarty();
		$privateTemplateManager->setTemplateDir($privateTemplatesDir);
		$privateTemplateManager->setCompileDir($cacheDir);

		$HtmlCreationService = new HTMLCreationService($publicTemplateManager, $privateTemplateManager);

		$submissionFile = Repo::submissionFile()->get($fileId);
		$jatsDocument = new Document($fileMgr->getBasePath() . DIRECTORY_SEPARATOR . $submissionFile->getData('path'));
		$citeProc = new HTMLDocument($jatsDocument);
		$dom = new \DOMDocument('1.0', 'utf-8');
		$htmlHead = "<!DOCTYPE html><head><meta http-equiv='Content-Type' content='text/html'; charset=utf-8/></head>";
		$dom->loadHTML($htmlHead . $htmlString);
		$xpath = new \DOMXPath($dom);

		$citationStyle = $plugin->getCitationStyle(\DAORegistry::getDAO('JournalDAO')->getById($journalId));
		$citeProc->setReferences($citationStyle, $localeKey, false);

    return $HtmlCreationService->buildHTML($htmlString, $xpath, $dom, $citeProc, $configuration, $metadata, $selectedTemplate, $ojsConfiguration);
  }

}