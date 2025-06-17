<?php namespace JATSParser\PDF\Templates\TemplateOne\Components;

use JATSParser\PDF\Templates\GenericComponent;
use JATSParser\PDF\Templates\Renderers\SingleRenderer\NoLinkableText;

class Footer extends GenericComponent{

        public function render(){
            //GET FOOTER CONFIGURATION
            $footerConfig = $this->config->getFooterConfig();
            $sectionTitle = strtoupper($this->config->getSectionTitle());
            
            $this->pdfTemplate->SetLeftMargin(20);

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
                $sectionTitle,
                $xPos,
                $yPos,
                $footerConfig['config']['section_title']['text_color'],
                $footerConfig['config']['section_title']['font'],
            );
            
            // Agregar el número de página en la parte derecha
            NoLinkableText::renderNoLinkableText(
                $this->pdfTemplate,
                $this->pdfTemplate->getAliasNumPage(),
                $xPos,
                $yPos,
                $footerConfig['config']['page_number']['text_color'],
                $footerConfig['config']['page_number']['font'],
                'R'
            );
        }

    }