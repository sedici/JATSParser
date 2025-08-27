<?php namespace JATSParser\PDF\Templates\Renderers\Utils;

class ReferenceRenderer {
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
     * Render references section
     * 
     * @param array $references References array
     * @param array $refs Reference links
     */
    public function render(array $references, array $refs): void {
        if (empty($references)) {
            return;
        }
        
        // Render title
        $this->pdfTemplate->writeHTML(
            '<h2>' . __('plugins.generic.jatsParser.article.references.title') . '</h2>', 
            false, false, true, false, ''
        );
        
        $this->pdfTemplate->Ln(5);
        
        // Render each reference
        foreach ($refs as $refId => $linkId) {
            if (isset($references[$refId])) {
                // Capture current Y position for precise linking
                $yPosition = $this->pdfTemplate->GetY();
                
                // Set link with exact Y position
                $this->pdfTemplate->SetLink($linkId, $yPosition);
                
                // Render the reference
                $this->pdfTemplate->writeHTML($references[$refId], false, false, true, false, '');
                $this->pdfTemplate->SetLeftMargin($this->leftMargin); // temporal fix, margin left error
                $this->pdfTemplate->Ln(10);
            }
        }
        
        $this->pdfTemplate->Ln(5);
    }
}
