<?php

namespace JATSParser\TemplateHandler\EPUB;

use JATSParser\Body\Document;
use JATSParser\HTML\Document as HTMLDocument;
use APP\facades\Repo;
use JATSParser\TemplateHandler\OutputStrategy;

class EPUBOutputStrategy implements OutputStrategy {

    public static function generateOutput($plugin, $fileMgr, $journalId, $localeKey, $fileId, $htmlString, $configuration, $metadata, $ojsConfiguration)
    {
        libxml_use_internal_errors(true);

        $epubCreationService = new EPUBCreationService();

        // Cargar XML JATS original para referencias y metadatos complementarios
        $submissionFile = Repo::submissionFile()->get($fileId);
        $xmlPath = $submissionFile ? $fileMgr->getBasePath() . DIRECTORY_SEPARATOR . $submissionFile->getData('path') : '';
        $jatsDocument = new Document($xmlPath);
        if (method_exists($configuration, 'setXmlFilePath')) {
            $configuration->setXmlFilePath($xmlPath);
        }

        // Cargar DOM para procesar el HTML
        $citeProc = new HTMLDocument($jatsDocument);
        $dom = new \DOMDocument('1.0', 'utf-8');
        $htmlHead = "<!DOCTYPE html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'/></head>";
        $dom->loadHTML($htmlHead . $htmlString);
        $xpath = new \DOMXPath($dom);

        // Estilo de citas
        $citationStyle = $plugin->getCitationStyle(\DAORegistry::getDAO('JournalDAO')->getById($journalId));
        $citeProc->setReferences($citationStyle, str_replace('_', '-', $localeKey), false);

        // Construir el EPUB
        $result = $epubCreationService->buildEPUB($plugin, $fileMgr, $journalId, $localeKey, $fileId, $htmlString, $xpath, $dom, $citeProc, $configuration, $metadata, $ojsConfiguration);

        libxml_use_internal_errors(false);
        return $result;
    }
}