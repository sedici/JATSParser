<?php

namespace JATSParser\TemplateHandler\EPUB\Content;

use DOMDocument;
use DOMXPath;
use DOMElement;

class XHTMLNormalizer
{
    /**
     * Convierte el árbol DOM en un documento XHTML 1.1 / EPUB 3 estricto y válido
     */
    public function toXhtml(DOMDocument $dom, string $title, string $lang, string $cssFileName = 'styles.css'): string
    {
        $this->removeDisallowedTags($dom);
        $this->convertJatsTags($dom);
        $this->convertExtLinks($dom);
        $this->fixOrphanListItems($dom);
        $this->sanitizeIds($dom);
        $this->deduplicateIds($dom);
        $this->removeBrokenFragmentLinks($dom);

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
     * Convierte etiquetas propias de JATS XML residuales a etiquetas XHTML estándar
     */
    private function convertJatsTags(DOMDocument $dom): void
    {
        $xpath = new DOMXPath($dom);
        $tagsMap = [
            'italic' => 'i',
            'bold' => 'b',
            'underline' => 'u',
            'monospace' => 'code',
            'disp-quote' => 'blockquote',
        ];

        foreach ($tagsMap as $jatsTag => $htmlTag) {
            $nodes = $xpath->query('//' . $jatsTag);
            foreach ($nodes as $node) {
                if (!($node instanceof DOMElement)) {
                    continue;
                }
                $newNode = $dom->createElement($htmlTag);
                while ($node->firstChild) {
                    $newNode->appendChild($node->firstChild);
                }
                foreach ($node->attributes as $attr) {
                    $newNode->setAttribute($attr->nodeName, $attr->nodeValue);
                }
                $node->parentNode->replaceChild($newNode, $node);
            }
        }

        // Small-caps <sc> -> <span class="small-caps">
        $scNodes = $xpath->query('//sc');
        foreach ($scNodes as $node) {
            if (!($node instanceof DOMElement)) {
                continue;
            }
            $span = $dom->createElement('span');
            $span->setAttribute('class', 'small-caps');
            while ($node->firstChild) {
                $span->appendChild($node->firstChild);
            }
            $node->parentNode->replaceChild($span, $node);
        }
    }

    /**
     * Convierte etiquetas propias de JATS XML (<ext-link>) a etiquetas HTML estándar (<a>)
     */
    private function convertExtLinks(DOMDocument $dom): void
    {
        $xpath = new DOMXPath($dom);
        $extLinks = $xpath->query('//ext-link');

        foreach ($extLinks as $extLink) {
            if (!($extLink instanceof DOMElement)) {
                continue;
            }

            $a = $dom->createElement('a');
            $href = $extLink->getAttribute('xlink:href') ?: $extLink->getAttribute('href');
            if ($href !== '') {
                $a->setAttribute('href', $href);
            }

            while ($extLink->firstChild) {
                $a->appendChild($extLink->firstChild);
            }

            $extLink->parentNode->replaceChild($a, $extLink);
        }
    }

    /**
     * Enuelve elementos <li> huérfanos dentro de listas <ul> para cumplir con XHTML estricto
     */
    private function fixOrphanListItems(DOMDocument $dom): void
    {
        $xpath = new DOMXPath($dom);
        $orphanLis = $xpath->query('//li[not(parent::ul or parent::ol)]');

        foreach ($orphanLis as $li) {
            if (!($li instanceof DOMElement)) {
                continue;
            }

            $parent = $li->parentNode;
            if (!$parent || in_array(strtolower($parent->nodeName), ['ul', 'ol'])) {
                continue;
            }

            // Si el hermano anterior es una lista generada previamente, concatenar el elemento allí
            $prev = $li->previousSibling;
            while ($prev && $prev->nodeType === XML_TEXT_NODE && trim($prev->textContent) === '') {
                $prev = $prev->previousSibling;
            }

            if ($prev instanceof DOMElement && strtolower($prev->nodeName) === 'ul' && $prev->getAttribute('data-epub-list') === 'true') {
                $prev->appendChild($li);
            } else {
                $ul = $dom->createElement('ul');
                $ul->setAttribute('data-epub-list', 'true');
                $ul->setAttribute('class', 'ref-list');
                $parent->insertBefore($ul, $li);
                $ul->appendChild($li);
            }
        }
    }

    /**
     * Elimina IDs duplicados para cumplir con la restricción estricta de IDs únicos en XML/XHTML
     */
    private function deduplicateIds(DOMDocument $dom): void
    {
        $xpath = new DOMXPath($dom);
        $seen = [];

        foreach ($xpath->query('//*[@id]') as $node) {
            if (!($node instanceof DOMElement)) {
                continue;
            }

            $id = $node->getAttribute('id');
            if ($id === '') {
                continue;
            }

            if (isset($seen[$id])) {
                $node->removeAttribute('id');
            } else {
                $seen[$id] = true;
            }
        }
    }

    /**
     * Sanea los atributos id, name y enlaces a fragmentos para que no contengan espacios
     */
    private function sanitizeIds(DOMDocument $dom): void
    {
        $xpath = new DOMXPath($dom);

        // 1. Saneamiento de id="..."
        foreach ($xpath->query('//*[@id]') as $node) {
            if (!($node instanceof DOMElement)) {
                continue;
            }
            $id = $node->getAttribute('id');
            $cleanId = trim(preg_replace('/\s+/', '_', $id));
            if ($cleanId === '') {
                $node->removeAttribute('id');
            } elseif ($cleanId !== $id) {
                $node->setAttribute('id', $cleanId);
            }
        }

        // 2. Saneamiento de anclas <a name="...">
        foreach ($xpath->query('//a[@name]') as $node) {
            if (!($node instanceof DOMElement)) {
                continue;
            }
            $name = $node->getAttribute('name');
            $cleanName = trim(preg_replace('/\s+/', '_', $name));
            if ($cleanName === '') {
                $node->removeAttribute('name');
            } elseif ($cleanName !== $name) {
                $node->setAttribute('name', $cleanName);
            }
        }

        // 3. Sincronización de enlaces internos a fragmentos <a href="#...">
        foreach ($xpath->query('//a[@href]') as $node) {
            if (!($node instanceof DOMElement)) {
                continue;
            }
            $href = $node->getAttribute('href');
            if (strpos($href, '#') === 0) {
                $target = substr($href, 1);
                $cleanTarget = trim(preg_replace('/\s+/', '_', $target));
                if ($cleanTarget !== $target) {
                    $node->setAttribute('href', '#' . $cleanTarget);
                }
            }
        }
    }

    /**
     * Neutraliza enlaces a fragmentos (#id) inexistentes para evitar errores RSC-012 en EPUBCheck
     */
    private function removeBrokenFragmentLinks(DOMDocument $dom): void
    {
        $xpath = new DOMXPath($dom);
        $validTargets = [];

        foreach ($xpath->query('//*[@id]') as $node) {
            $id = $node->getAttribute('id');
            if ($id !== '') {
                $validTargets[$id] = true;
            }
        }

        foreach ($xpath->query('//a[@name]') as $node) {
            $name = $node->getAttribute('name');
            if ($name !== '') {
                $validTargets[$name] = true;
            }
        }

        foreach ($xpath->query('//a[@href]') as $node) {
            if (!($node instanceof DOMElement)) {
                continue;
            }
            $href = $node->getAttribute('href');
            if (strpos($href, '#') === 0) {
                $target = substr($href, 1);
                if (!isset($validTargets[$target])) {
                    // El destino no existe en el documento: desarmar href para evitar enlace roto
                    $node->removeAttribute('href');
                    $node->setAttribute('class', trim($node->getAttribute('class') . ' unlinked-ref'));
                }
            }
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
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops" xmlns:xlink="http://www.w3.org/1999/xlink" xml:lang="{$lang}" lang="{$lang}">
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