<?php namespace JATSParser\PDF\Templates\TemplateOne\Components;

use JATSParser\PDF\PDFBodyHelper;
use JATSParser\PDF\Templates\GenericComponent;
use JATSParser\PDF\Templates\Renderers\Utils\ReferenceExtractor;
use JATSParser\PDF\Templates\Renderers\Utils\FootnoteExtractor;
use JATSParser\PDF\Templates\Renderers\Utils\ContentRenderer;
use JATSParser\PDF\Templates\Renderers\Utils\ReferenceRenderer;
use JATSParser\PDF\Templates\Renderers\Utils\FootnoteRenderer;

class Body extends GenericComponent {

    public function render() {
        // Setup basic configuration
        $bodyFont = $this->config->getFontConfig('philosopher');
        $htmlString = $this->config->getMetadata('html_string');
        $pluginPath = $this->config->getMetadata('plugin_path');
        $leftMargin = $this->config->getMargin('body_left');

        $this->pdfTemplate->SetLeftMargin($leftMargin);
        $this->pdfTemplate->SetRightMargin($leftMargin);
        $this->pdfTemplate->SetFont($bodyFont['family'], $bodyFont['style'], $bodyFont['size']);

        // Add CSS styles
        $htmlString .= "\n" . '<style>' . "\n" . file_get_contents($pluginPath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'styles' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'pdfGalley.css') . '</style>';
        
        // Initialize references array
        $refs = [];
        
        // Prepare HTML for PDF galley
        $htmlString = PDFBodyHelper::_prepareForPdfGalley($htmlString, $this->config, $this->pdfTemplate, $refs);
        
        // Extract references and footnotes
        $referenceExtractor = new ReferenceExtractor();
        $references = $referenceExtractor->extract($htmlString);
        
        $footnoteExtractor = new FootnoteExtractor();
        $footnotes = $footnoteExtractor->extract($htmlString);
        
        // Remove reference and footnote sections from HTML
        $htmlString = $this->removeRefsAndFn($htmlString);
        
        // Render the main content
        $contentRenderer = new ContentRenderer($this->pdfTemplate, $this->config, $leftMargin);
        $contentRenderer->render($htmlString, $refs, $footnotes);
        
        // Add a new page for references and footnotes
        $this->pdfTemplate->AddPage();
        $this->pdfTemplate->SetLeftMargin($leftMargin);
        $this->pdfTemplate->SetFillColor(255, 255, 255);
        
        // Render references
        $referenceRenderer = new ReferenceRenderer($this->pdfTemplate, $this->config, $leftMargin);
        $referenceRenderer->render($references, $refs);
        
        // Render footnotes
        $footnoteRenderer = new FootnoteRenderer($this->pdfTemplate, $this->config, $leftMargin);
        $footnoteRenderer->render($footnotes, $refs);

        file_put_contents(
            __DIR__ . '/debug.html',
            $htmlString
        );
    }
    
    /**
     * Remove references and footnotes sections from HTML
     * 
     * @param string $htmlString The HTML content
     * @return string HTML without meta sections
     */
    private function removeRefsAndFn(string $htmlString): string {
        // Eliminar todo el contenedor de referencias-section y su contenido
        $htmlString = preg_replace('/<div[^>]*class\s*=\s*"[^"]*references-section[^"]*"[^>]*>.*<\/div>/is', '', $htmlString);
        
        // Eliminar todo el contenedor de footnotes-container y su contenido
        $htmlString = preg_replace('/<div[^>]*class\s*=\s*"[^"]*footnotes-container[^"]*"[^>]*>.*<\/div>/is', '', $htmlString);
        
        return $htmlString;
    }
}