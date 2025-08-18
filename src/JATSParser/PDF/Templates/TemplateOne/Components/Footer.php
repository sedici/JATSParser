<?php namespace JATSParser\PDF\Templates\TemplateOne\Components;

use JATSParser\PDF\Templates\GenericComponent;
use JATSParser\PDF\Templates\Renderers\SingleRenderer\NoLinkableText;

class Footer extends GenericComponent{

    public function render(){
        // Obtener metadatos y configuración visual
        $sectionTitle = strtoupper($this->config->getMetadata('section_title'));
        $footerFont = $this->config->getFontConfig('default');
        $footerFontBold = $this->config->getFontConfig('bold');
        $sectionTitleFont = $this->config->getFontConfig('default');
        $sectionTitleColor = $this->config->getColorConfig('accent');
        $pageNumberFont = $this->config->getFontConfig('default');
        $pageNumberColor = $this->config->getColorConfig('black');
        $leftMargin = $this->config->getMargin('footer_left');

        $this->pdfTemplate->SetLeftMargin($leftMargin);

        // Draw a horizontal line across the page
        $this->pdfTemplate->SetDrawColor(100, 100, 100); // Grey color for the line
        $this->pdfTemplate->SetLineWidth(0.1); // Line width
        $lineY = $this->pdfTemplate->GetY(); // Y position of the line
        $this->pdfTemplate->Line(
            $this->pdfTemplate->GetPageWidth() * 0.09, // 5% to the left edge
            $lineY,
            $this->pdfTemplate->GetPageWidth() * 0.90, // 95% to the right edge
            $lineY
        );

        $xPos = $this->pdfTemplate->GetX();
        $yPos = $this->pdfTemplate->GetY() + 2;

        NoLinkableText::renderNoLinkableText(
            $this->pdfTemplate,
            ucfirst(strtolower($sectionTitle)),
            $xPos,
            $yPos,
            $sectionTitleColor,
            $sectionTitleFont
        );

        // Agregar el número de página en la parte derecha
        NoLinkableText::renderNoLinkableText(
            $this->pdfTemplate,
            $this->pdfTemplate->getAliasNumPage(),
            $xPos,
            $yPos,
            $pageNumberColor,
            $pageNumberFont,
            'R'
        );
    }
}