<?php namespace JATSParser\PDF;

class CitationMarker {

    // Retorna el HTML formateado y llena $links (por referencia) con los enlaces generados
    public static function markCitations($htmlString, $config, $pdfTemplate, &$links) {
        $links = [];
        $refs = [];

        $dom = new \DOMDocument('1.0', 'UTF-8');
        \libxml_use_internal_errors(true);
        $dom->loadHTML(
            mb_convert_encoding($htmlString, 'HTML-ENTITIES', 'UTF-8'),
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        \libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        // Buscar todos los <li> dentro de .references-section
        $referencesNodes = $xpath->evaluate('//div[contains(@class,"references-section")]//li');
        foreach ($referencesNodes as $refNode) {
            $id = $refNode->getAttribute('id');
            if ($id !== '') {
                $refs[$id] = $pdfTemplate->AddLink();
            }
        }

        // Reemplazar <a.bibr> por marcadores {{LINK:...}} o {{MULTILINK:...}}
        foreach ($xpath->query('//a[contains(@class, "bibr")]') as $a) {
            $href = ltrim($a->getAttribute('href'), '#');
            if (strpos($href, '%20') === false && isset($refs[$href])) {
                $a->parentNode->replaceChild(
                    $dom->createTextNode("{{LINK:$href:" . $a->nodeValue . "}}"),
                    $a
                );
            } else {
                $text = "{{MULTILINK:$href:" . $a->nodeValue . "}}";
                $a->parentNode->replaceChild(
                    $dom->createTextNode($text),
                    $a
                );
            }
        }

        // Buscar todas las footnotes dentro de .footnotes-container
        $footnotesNodes = $xpath->evaluate('//div[contains(@class,"footnotes-container")]//div');
        foreach ($footnotesNodes as $node) {
            $id = $node->getAttribute('id');
            if ($id !== '') {
                if (strpos($id, 'fn-') === 0) {
                    $id = substr($id, 3); // quitar 'fn-'
                }
                $refs[$id] = $pdfTemplate->AddLink();
            }
        }

        // Reemplazar <a.fn> por marcadores {{LINK:...}} o {{MULTILINK:...}}
        foreach ($xpath->query('//a[contains(@class, "fn")]') as $a) {
            $href = ltrim($a->getAttribute('href'), '#');
            if (strpos($href, '%20') === false && isset($refs[$href])) {
                $a->parentNode->replaceChild(
                    $dom->createTextNode("{{LINK:$href:" . $a->nodeValue . "}}"),
                    $a
                );
            } else {
                $text = "{{MULTILINK:$href:" . $a->nodeValue . "}}";
                $a->parentNode->replaceChild(
                    $dom->createTextNode($text),
                    $a
                );
            }
        }

        // Buscar todos los anchors de las footnotes
        $footnotesAnchors = $xpath->evaluate('//div[contains(@class,"footnotes-container")]//div//a[contains(@data-ref-type,"bibr")]');
        foreach($footnotesAnchors as $anchor) {
            $href = ltrim($anchor->getAttribute('href'), '#');
            if (strpos($href, '%20') === false && isset($refs[$href])) {
                error_log("Link found: {{LINK:$href:" . $anchor->nodeValue . "}}");
                $anchor->parentNode->replaceChild(
                    $dom->createTextNode("{{LINK:$href:" . $anchor->nodeValue . "}}"),
                    $anchor
                );
            } 
            else {
                error_log("Multi-link found: {{MULTILINK:$href:" . $anchor->nodeValue . "}}");
                $text = "{{MULTILINK:$href:" . $anchor->nodeValue . "}}";
                $anchor->parentNode->replaceChild(
                    $dom->createTextNode($text),
                    $anchor
                );
            }
        }

        // Exponer el mapa de links y retornar el HTML modificado
        $links = $refs;
        return $dom->saveHTML();
    }

}