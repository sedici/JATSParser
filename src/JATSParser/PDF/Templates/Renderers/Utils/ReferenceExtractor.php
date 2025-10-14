<?php namespace JATSParser\PDF\Templates\Renderers\Utils;

class ReferenceExtractor {
    /**
     * Extract references from HTML content
     * 
     * @param string $htmlString The HTML content
     * @return array References as [id => html]
     */
    public function extract(string $htmlString): array {
        $domRefs = new \DOMDocument();
        libxml_use_internal_errors(true);
        $domRefs->loadHTML('<?xml encoding="utf-8" ?>' . $htmlString);
        libxml_clear_errors();
        $xpathRefs = new \DOMXPath($domRefs);

        $references = [];
        foreach ($xpathRefs->query('//li[contains(@class,"citation-item")]') as $li) {
            $refId = $li->getAttribute('id');
            $references[$refId] = $domRefs->saveHTML($li);
        }
        
        return $references;
    }
}
