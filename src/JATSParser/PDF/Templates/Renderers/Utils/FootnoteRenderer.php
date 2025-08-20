<?php namespace JATSParser\PDF\Templates\Renderers\Utils;

class FootnoteRenderer {
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
     * Render footnotes section
     * 
     * @param array $footnotes Footnotes array
     * @param array $refs Reference links
     */
    public function render($footnotes, array $refs): void {
        if (empty($footnotes)) {
            return;
        }
        
        // Render title
        $this->pdfTemplate->writeHTML(
            '<h2>' . __('plugins.generic.jatsParser.article.footnotes.title') . '</h2>', 
            false, false, true, false, ''
        );
        // Asegurar margen y X correctos antes de la primera nota
        $this->pdfTemplate->SetLeftMargin($this->leftMargin);
        $this->pdfTemplate->SetX($this->leftMargin);
        $this->pdfTemplate->Ln(5);
        
        // Render each footnote
        foreach ($footnotes as $noteId => $noteHtml) {
            // Forzar margen y X antes de escribir cada nota
            $this->pdfTemplate->SetLeftMargin($this->leftMargin);
            $this->pdfTemplate->SetX($this->leftMargin);

            // Capture current Y position for precise linking
            $yPosition = $this->pdfTemplate->GetY();
            
            $refKey = $noteId;
            if (!isset($refs[$refKey]) && strpos($refKey, 'fn-') === 0) {
                // Si no existe, intenta sin el prefijo
                $refKeyNoFn = substr($refKey, 3);
                if (isset($refs[$refKeyNoFn])) {
                    $this->pdfTemplate->SetLink($refs[$refKeyNoFn], $yPosition);
                }
            } else if (isset($refs[$refKey])) {
                $this->pdfTemplate->SetLink($refs[$refKey], $yPosition);
            }
            
            // Render the footnote (soporta {{LINK|MULTILINK}} como referencias; sin tablas)
            $pattern = '/({{(?:LINK|MULTILINK):[^:]+:[^}]+}})/is';
            if (preg_match($pattern, $noteHtml)) {
                $parts = preg_split($pattern, $noteHtml, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
                foreach ($parts as $i => $part) {
                    if (preg_match('/{{(LINK|MULTILINK):([^:]+):(.+?)}}/', $part, $m)) {
                        // Tratar todos los marcadores como referencias (no footnotes)
                        $this->renderReferenceLink($m[3], $i, $parts, $refs, $m[2]);
                    } else {
                        $this->pdfTemplate->writeHTML($part, false, false, true, false, '');
                        $this->pdfTemplate->SetLeftMargin($this->leftMargin);
                    }
                }
            } else {
                $this->pdfTemplate->writeHTML($noteHtml, false, false, true, false, '');
            }

            $this->pdfTemplate->Ln(6);
        }
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

    // Renderizar un link a referencia (basado en ContentRenderer::renderReferenceLink, sin lógica de footnotes)
    private function renderReferenceLink(string $text, int $i, array $parts, array $links, string $refId): void {
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

        // Estilo de enlace como en ContentRenderer
        $this->pdfTemplate->SetTextColor(50, 132, 156);

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

        // Restaurar color
        $this->pdfTemplate->SetTextColor(0, 0, 0);

        // Agregar espacio después salvo que el siguiente fragmento comience con puntuación
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
}
