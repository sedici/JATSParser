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
    public function render(array $footnotes, array $refs): void {
        if (empty($footnotes)) {
            return;
        }
        
        // Render title
        $this->pdfTemplate->writeHTML(
            '<h2>' . __('plugins.generic.jatsParser.article.footnotes.title') . '</h2>', 
            false, false, true, false, ''
        );
        
        $this->pdfTemplate->Ln(5);
        
        // Render each footnote
        foreach ($footnotes as $noteId => $noteHtml) {
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
            
            // Render the footnote
            $this->pdfTemplate->writeHTML($noteHtml, false, false, true, false, '');
            $this->pdfTemplate->SetLeftMargin($this->leftMargin); // temporal fix, margin left error
            $this->pdfTemplate->Ln(6);
        }
    }
}
