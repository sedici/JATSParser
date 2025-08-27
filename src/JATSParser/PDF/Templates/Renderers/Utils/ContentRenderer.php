<?php namespace JATSParser\PDF\Templates\Renderers\Utils;

class ContentRenderer {
    /** @var \TCPDF */
    private $pdfTemplate;
    
    /** @var object */
    private $config;
    
    /** @var float */
    private $leftMargin;
    
    /**
     * Constructor
     * 
     * @param \TCPDF $pdfTemplate The PDF template
     * @param object $config The configuration
     * @param float $leftMargin Left margin
     */
    public function __construct($pdfTemplate, $config, $leftMargin) {
        $this->pdfTemplate = $pdfTemplate;
        $this->config = $config;
        $this->leftMargin = $leftMargin;
    }
    
    /**
     * Render content with citations
     * 
     * @param string $htmlString The HTML content
     * @param array $links References array
     * @param array $footnotes Footnotes array
     */
    public function render(string $htmlString, array $links, array $footnotes): void {
        // Split content into parts based on links (tables, lists, or raw markers)
        $parts = preg_split(
            '/(<table\b[^>]*>(?:(?!<\/table>).)*{{(?:LINK|MULTILINK):[^:]+:[^}]+}}(?:(?!<\/table>).)*<\/table>|<ul\b[^>]*>(?:(?!<\/ul>).)*{{(?:LINK|MULTILINK):[^:]+:[^}]+}}(?:(?!<\/ul>).)*<\/ul>|<ol\b[^>]*>(?:(?!<\/ol>).)*{{(?:LINK|MULTILINK):[^:]+:[^}]+}}(?:(?!<\/ol>).)*<\/ol>|{{(?:LINK|MULTILINK):[^:]+:[^}]+}})/is',
            $htmlString,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );

        // Process each part
        foreach ($parts as $i => $part) {
            if (preg_match('/<table\b[^>]*>(?:(?!<\/table>).)*{{(LINK|MULTILINK):[^:]+:[^}]+}}(?:(?!<\/table>).)*<\/table>/is', $part)) {
                $this->renderTableWithLink($part, $links, $footnotes);
            }
            // Detect UL/OL blocks containing a citation marker and render as a whole to avoid breaking the list
            else if (preg_match('/<(ul|ol)\b[^>]*>(?:(?!<\/\1>).)*{{(?:LINK|MULTILINK):[^:]+:[^}]+}}(?:(?!<\/\1>).)*<\/\1>/is', $part)) {
                $this->renderListWithLink($part, $links, $footnotes);
            }
            else if (preg_match('/{{(LINK|MULTILINK):([^:]+):(.+?)}}/', $part, $match)) {
                $linkType = $match[1];
                $refId = $match[2];
                $text = $match[3];
                $this->renderLink($linkType, $refId, $text, $i, $parts, $links, $footnotes);
            } else {
                $this->renderHtml($part);
            }
        }
    }

    /**
     * Render table with link
     * 
     * @param string $part HTML table with link
     */
    private function renderTableWithLink(string $part, $links, $footnotes): void {
        $part = preg_replace_callback(
            '/{{(LINK|MULTILINK):([^:]+):(.+?)}}/',
            function ($matches) {
                return $matches[3];
            },
            $part
        );

        $this->pdfTemplate->Ln(3);
        $this->pdfTemplate->writeHTML($part, false, false, true, false, '');
        $this->pdfTemplate->SetLeftMargin($this->leftMargin); // temporal fix, margin left error
    }

    /**
     * Render list (ul/ol) with link
     * Processes the whole list block to avoid breaking list structure when splitting by markers.
     */
    private function renderListWithLink(string $part, $links, $footnotes): void {
        // add <p> wrapper para evitar espacio antes de notas al pie
        $part = '<p></p>' . $part . '<p></p>';

        // Reemplazar todas las ocurrencias por el texto visible
        $part = preg_replace_callback(
            '/{{(LINK|MULTILINK):([^:]+):(.+?)}}/',
            function ($matches) {
                return $matches[3];
            },
            $part
        );

        $this->pdfTemplate->writeHTML($part, false, false, true, false, '');
        $this->pdfTemplate->SetLeftMargin($this->leftMargin); // temporal fix, margin left error
    }
    
    /**
     * Render a link
     * 
     * @param string $linkType Type of link (LINK or MULTILINK)
     * @param string $refId Reference ID(s)
     * @param string $text Text to display
     * @param int $i Current part index
     * @param array $parts All content parts
     * @param array $links References array
     * @param array $footnotes Footnotes array
     */
    private function renderLink(string $linkType, string $refId, string $text, int $i, array $parts, array $links, array $footnotes): void {
        $this->pdfTemplate->SetTextColor(50, 132, 156); // rgb(50,132,156)

        // Unify: decide footnote vs reference looking at IDs (supports single or multiple)
        if ($this->allIdsAreFootnotes($refId, $footnotes)) {
            $this->renderFootnoteLink($text, $i, $parts, $links, $refId, $footnotes);
        } else {
            $this->renderReferenceLink($text, $i, $parts, $links, $refId, $footnotes);
        }

        $this->pdfTemplate->SetTextColor(0, 0, 0);
        $this->pdfTemplate->SetLeftMargin($this->leftMargin); // temporal fix, margin left error
    }

    // Helper: split IDs by %20, spaces or commas and normalize 'fn-' prefix
    private function parseIds(string $refId): array {
        $ids = preg_split('/(?:%20|\s|,)+/', trim($refId), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (!$ids) { return []; }
        return array_map(function ($id) {
            $id = trim($id);
            return (strpos($id, 'fn-') === 0) ? substr($id, 3) : $id;
        }, $ids);
    }

    // Helper: all parsed IDs are footnotes?
    private function allIdsAreFootnotes(string $refId, array $footnotes): bool {
        $ids = $this->parseIds($refId);
        if (!$ids) { return false; }
        foreach ($ids as $id) {
            if (!isset($footnotes[$id])) { return false; }
        }
        return true;
    }
    
    /**
     * Render a footnote link
     * 
     * @param string $text Link text
     * @param int $i Current part index
     * @param array $parts All content parts
     * @param array $links References array
     * @param string $refId Reference ID
     * @param array $footnotes Footnotes array
     */
    private function renderFootnoteLink(string $text, int $i, array $parts, array $links, string $refId, array $footnotes): void {
        // Nunca agregar espacio antes de una nota al pie

        $ids = $this->parseIds($refId);
        if (!$ids) { $ids = [$refId]; }
        $textParts = array_map('trim', explode(',', $text)); 

        $currentFont = $this->pdfTemplate->getFontFamily();
        $currentStyle = $this->pdfTemplate->getFontStyle();
        $currentSize = $this->pdfTemplate->getFontSizePt();

        $this->pdfTemplate->SetFont($currentFont, $currentStyle, $currentSize * 0.7);

        for ($j = 0; $j < count($textParts); $j++) {
            $currentRefId = isset($ids[$j]) ? $ids[$j] : end($ids);

            // resolver link: probar id y fn-id
            $link = '';
            if (isset($links[$currentRefId])) {
                $link = $links[$currentRefId];
            }
            $this->pdfTemplate->Write(0, $textParts[$j], $link, 0);

            if ($j < count($textParts) - 1) {
                $this->pdfTemplate->Write(0, ',', '', 0);
            }
        }

        $this->pdfTemplate->SetFont($currentFont, $currentStyle, $currentSize);

        $addSpace = true;
        if (isset($parts[$i + 1])) {
            $nextPart = $parts[$i + 1];
            if (preg_match('/^[\.,;:]/', $nextPart)) {
                $addSpace = false;
            }
        }
        if ($addSpace) {
            $this->pdfTemplate->Write(0, ' ', '', 0);
        }
    }
    
    /**
     * Render a reference link
     * 
     * @param string $text Link text
     * @param int $i Current part index
     * @param array $parts All content parts
     * @param array $links References array
     * @param string $refId Reference ID
     * @param array $footnotes Footnotes array
     */
    private function renderReferenceLink(string $text, int $i, array $parts, array $links, string $refId, array $footnotes): void {
        // No agregar espacio antes si el texto anterior termina en (
        $addSpaceBefore = true;
        if (isset($parts[$i - 1])) {
            $prevPart = $parts[$i - 1];
            if (substr(rtrim($prevPart), -1) === '(') {
                $addSpaceBefore = false;
            }
        }
        if ($addSpaceBefore) {
            $this->pdfTemplate->Write(0, ' ', '', 0);
        }

        $ids = $this->parseIds($refId);
        if (!$ids) { $ids = [$refId]; }
        $textParts = array_map('trim', explode(';', $text));

        // Render cada parte con estilo de referencia
        for ($j = 0; $j < count($textParts); $j++) {
            $currentRefId = isset($ids[$j]) ? $ids[$j] : end($ids);
            if (isset($links[$currentRefId])) {
                $this->pdfTemplate->Write(0, $textParts[$j], $links[$currentRefId], 0);
            } else {
                $this->pdfTemplate->Write(0, $textParts[$j], '', 0);
            }

            if ($j < count($textParts) - 1) {
                $this->pdfTemplate->Write(0, '; ', '', 0);
            }
        }

        $addSpace = true;
        if (isset($parts[$i + 1])) {
            $nextPart = $parts[$i + 1];

            $isFootnoteLink = false;
            if (preg_match('/^{{(?:LINK|MULTILINK):([^:]+):(.+?)}}/', $nextPart, $nextMatch)) {
                // Normalizar id para comprobar si es nota al pie
                $nextRefId = (strpos($nextMatch[1], 'fn-') === 0) ? substr($nextMatch[1], 3) : $nextMatch[1];
                if (isset($footnotes[$nextRefId])) {
                    $isFootnoteLink = true;
                }
            }

            if (preg_match('/^[\.,;:]/', $nextPart) || $isFootnoteLink) {
                $addSpace = false;
            }
        }

        if ($addSpace) {
            $this->pdfTemplate->Write(0, ' ', '', 0);
        }
    }
    
    /**
     * Render HTML content
     * 
     * @param string $html HTML content
     */
    private function renderHtml(string $html): void {
        // Eliminar espacios en blanco al final para evitar un espacio antes de footnotes
        $html = preg_replace('/\s+$/u', '', $html);
        $this->pdfTemplate->writeHTML($html, false, false, true, false, '');
        $this->pdfTemplate->SetLeftMargin($this->leftMargin); // temporal fix, margin left error
    }
}
