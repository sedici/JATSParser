<?php namespace JATSParser\PDF\Templates\Renderers\Utils;

class FootnoteExtractor {
    /**
     * Extract footnotes from HTML content
     * 
     * @param string $htmlString The HTML content
     * @return array Footnotes as [id => html]
     */
    public function extract(string $htmlString): array {
        $domNotes = new \DOMDocument();
        libxml_use_internal_errors(true);
        $domNotes->loadHTML('<?xml encoding="utf-8" ?>' . $htmlString);
        libxml_clear_errors();
        $xpathNotes = new \DOMXPath($domNotes);

        $footnotes = [];
        foreach ($xpathNotes->query('//div[contains(@class,"footnote-item")]') as $div) {
            $noteId = $div->getAttribute('id');
            if (strpos($noteId, 'fn-') === 0) {
                $noteId = substr($noteId, 3); // quitar 'fn-' si existe
            }
            $footnotes[$noteId] = $domNotes->saveHTML($div);
        }
        
        return $footnotes;
    }
}
