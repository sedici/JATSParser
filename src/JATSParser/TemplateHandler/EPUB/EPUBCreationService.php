<?php

namespace JATSParser\TemplateHandler\EPUB;

use JATSParser\TemplateHandler\EPUB\Metadata\EPUBMetadataMapper;
use JATSParser\TemplateHandler\EPUB\Styles\EPUBStyleProvider;
use JATSParser\TemplateHandler\EPUB\Content\XHTMLNormalizer;
use JATSParser\TemplateHandler\EPUB\Content\EPUBResourceResolver;
use JATSParser\TemplateHandler\EPUB\Packaging\EPUBPackager;
use DOMDocument;
use DOMXPath;
use DOMElement;

class EPUBCreationService
{
    private $metadataMapper;
    private $styleProvider;
    private $resourceResolver;
    private $normalizer;
    private $epubPackager;

    /**
     * Inyección de dependencias (permite mockear en tests o instanciar por defecto)
     */
    public function __construct(
        $metadataMapper = null,
        $styleProvider = null,
        $resourceResolver = null,
        $normalizer = null,
        $epubPackager = null
    ) {
        $this->metadataMapper = $metadataMapper ?? new EPUBMetadataMapper();
        $this->styleProvider = $styleProvider ?? new EPUBStyleProvider();
        $this->resourceResolver = $resourceResolver ?? new EPUBResourceResolver();
        $this->normalizer = $normalizer ?? new XHTMLNormalizer();
        $this->epubPackager = $epubPackager ?? new EPUBPackager();
    }

    /**
     * Orquesta la creación del EPUB
     */
    public function buildEPUB(
        $plugin,
        $fileMgr,
        $journalId,
        $localeKey,
        $fileId,
        string $htmlString,
        $xpath,
        $dom,
        $citeProc,
        $config,
        array $metadata,
        array $ojsConfiguration
    ): string {

        $this->processDOM($xpath, $dom, $config, $citeProc);
        
        $bookMetadata = $this->metadataMapper->map($metadata, $localeKey, $fileId);

        $cssStyles = $this->styleProvider->getStyles();

        $resources = $this->resourceResolver->resolveImages($dom, $xpath, $fileMgr, $fileId);

        $chapterXhtml = $this->normalizer->toXhtml(
            $dom,
            $bookMetadata['title'],
            $bookMetadata['language'],
            'styles.css'
        );

        return $this->epubPackager->generate(
            $bookMetadata,
            $cssStyles,
            $chapterXhtml,
            $resources
        );
    }

    /**
     * Procesa citas, referencias, notas al pie, tablas y figuras
     */
    public function processDOM(DOMXPath $xpath, DOMDocument $dom, $config, $citeProc): void
    {
        $this->processBody($xpath, $dom, $config, $citeProc);
        $this->processReferences($xpath, $dom, $config, $citeProc);
        $this->processFootnotes($xpath, $dom, $config, $citeProc);
    }

    private function processBody(DOMXPath $xpath, DOMDocument $dom, $config, $citeProc): void
    {
        EPUBProcessingService::replaceCitationsContent($xpath, $config);

        $referencesNodes = $xpath->evaluate('//a[contains(@class, "bibr")]');
        foreach ($referencesNodes as $node) {
            EPUBProcessingService::citeToLink($node, $dom, $xpath, $config);
        }

        $footnotesNodes = $xpath->evaluate('//a[contains(@class, "fn")]');
        foreach ($footnotesNodes as $node) {
            EPUBProcessingService::footnoteToLink($node, $dom);
        }

        $tableNodes = $xpath->evaluate('//a[contains(@class, "table")]');
        foreach ($tableNodes as $node) {
            EPUBProcessingService::tableToLink($node, $dom);
        }

        $figNodes = $xpath->evaluate('//a[contains(@class, "fig")]');
        foreach ($figNodes as $node) {
            EPUBProcessingService::figureToLink($node, $dom);
        }

        // Crear instancia fresca de XPath para detectar los nuevos nodos y anclas insertadas
        $freshXpath = new DOMXPath($dom);

        EPUBProcessingService::addTableReturnArrows($dom, $freshXpath);
        EPUBProcessingService::addFigureReturnArrows($dom, $freshXpath);

        EPUBProcessingService::setTablesClass($freshXpath, 'table');
        EPUBProcessingService::setTablesClass($freshXpath, 'td');
        EPUBProcessingService::setTablesClass($freshXpath, 'tr');
        EPUBProcessingService::setTablesClass($freshXpath, 'th');
    }

    private function processReferences(DOMXPath $xpath, DOMDocument $dom, $config, $citeProc): void
    {
        $referencesAPA = $citeProc ? $citeProc->getRawReferences() : [];

        $referencesNodes = $xpath->evaluate('//div[contains(@class,"references-section")]//li');
        $references = EPUBProcessingService::setReferencesAnchors($referencesAPA, $referencesNodes);

        foreach ($xpath->query('//a[contains(@class, "bibr")]') as $a) {
            EPUBProcessingService::processCitations($a, $dom, "citation_");
        }

        $referencesSection = $xpath->query('//div[contains(@class,"references-section")]')->item(0);
        if ($referencesSection && !empty($references)) {
            $heading = null;
            foreach ($referencesSection->childNodes as $child) {
                if ($child instanceof DOMElement && preg_match('/^h[1-6]$/i', $child->nodeName)) {
                    $heading = $child->cloneNode(true);
                    break;
                }
            }

            while ($referencesSection->hasChildNodes()) {
                $referencesSection->removeChild($referencesSection->firstChild);
            }

            if ($heading) {
                $referencesSection->appendChild($heading);
            }

            EPUBProcessingService::processReferences($referencesSection, $references, $dom);
        }
    }

    private function processFootnotes(DOMXPath $xpath, DOMDocument $dom, $config, $citeProc): void
    {
        $footnotesNodes = $xpath->evaluate('//div[contains(@class,"footnotes-container")]//div[contains(@class, "footnote-item")]|//div[contains(@class,"footnotes-container")]//div');
        $footnotes = EPUBProcessingService::setFootnotesAnchors($footnotesNodes);

        foreach ($xpath->query('//div[contains(@class, "footnote-item")]') as $a) {
            EPUBProcessingService::processCitations($a, $dom, "footnote_");
        }

        $footnotesSection = $xpath->query('//div[contains(@class, "footnotes-container")]')->item(0);
        if ($footnotesSection && !empty($footnotes)) {
            $heading = null;
            foreach ($footnotesSection->childNodes as $child) {
                if ($child instanceof DOMElement && preg_match('/^h[1-6]$/i', $child->nodeName)) {
                    $heading = $child->cloneNode(true);
                    break;
                }
            }

            while ($footnotesSection->hasChildNodes()) {
                $footnotesSection->removeChild($footnotesSection->firstChild);
            }

            if ($heading) {
                $footnotesSection->appendChild($heading);
            }

            EPUBProcessingService::processFootnotes($footnotesSection, $footnotes, $dom);
        }
    }
}