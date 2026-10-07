<?php

namespace JATSParser\TemplateHandler\EPUB;

use JATSParser\TemplateHandler\EPUB\Metadata\EPUBMetadataMapper;
use JATSParser\TemplateHandler\EPUB\Styles\EPUBStyleProvider;
use JATSParser\TemplateHandler\EPUB\Content\XHTMLNormalizer;
use JATSParser\TemplateHandler\EPUB\Content\EPUBResourceResolver;
use JATSParser\TemplateHandler\EPUB\Packaging\EPUBPackager;

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
}