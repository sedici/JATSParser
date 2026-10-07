<?php

namespace JATSParser\TemplateHandler\EPUB\Content;

use DOMDocument;
use DOMXPath;

class XHTMLNormalizer
{
    /**
     * Convierte el árbol DOM en un documento XHTML 1.1 / EPUB 3 estricto y válido
     */
    public function toXhtml(DOMDocument $dom, string $title, string $lang, string $cssFileName = 'styles.css'): string
    {
        $this->removeDisallowedTags($dom);
        $bodyXml = $this->extractBodyXml($dom);
        $cleanBodyXml = $this->normalizeEntities($bodyXml);
        return $this->buildDocumentWrapper($cleanBodyXml, $title, $lang, $cssFileName);
    }

    /**
     * Elimina etiquetas no permitidas en un EPUB estándar (como scripts interactivos)
     */
    private function removeDisallowedTags(DOMDocument $dom): void
    {
        $xpath = new DOMXPath($dom);
        $disallowed = $xpath->query('//script|//noscript|//iframe');

        foreach ($disallowed as $node) {
            $node->parentNode->removeChild($node);
        }
    }

    /**
     * Extrae el contenido interno de <body> serializado como XML estricto
     */
    private function extractBodyXml(DOMDocument $dom): string
    {
        $bodyNodes = $dom->getElementsByTagName('body');
        if ($bodyNodes->length === 0) {
            return $dom->saveXML($dom->documentElement) ?: '';
        }

        $body = $bodyNodes->item(0);
        $xml = '';
        foreach ($body->childNodes as $child) {
            $xml .= $dom->saveXML($child) . "\n";
        }

        return trim($xml);
    }

    /**
     * Reemplaza entidades HTML que no son parte de las 5 entidades predefinidas de XML
     */
    private function normalizeEntities(string $xml): string
    {
        $replacements = [
            '&nbsp;' => '&#160;',
            '&copy;' => '&#169;',
            '&mdash;' => '&#8212;',
            '&ndash;' => '&#8211;',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $xml);
    }

    /**
     * Construye la cabecera, metadatos y envoltura XHTML del capítulo
     */
    private function buildDocumentWrapper(string $bodyContent, string $title, string $lang, string $cssFileName): string
    {
        $escapedTitle = htmlspecialchars($title, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $xhtml = <<<XHTML
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops" xml:lang="{$lang}" lang="{$lang}">
<head>
    <meta charset="utf-8" />
    <title>{$escapedTitle}</title>
    <link rel="stylesheet" type="text/css" href="{$cssFileName}" />
</head>
<body>
{$bodyContent}
</body>
</html>
XHTML;

        return trim($xhtml);
    }
}