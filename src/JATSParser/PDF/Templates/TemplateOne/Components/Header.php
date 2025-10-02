<?php namespace JATSParser\PDF\Templates\TemplateOne\Components;

use JATSParser\PDF\Templates\GenericComponent;
use JATSParser\PDF\Templates\Renderers\SingleRenderer\LinkableText;
use JATSParser\PDF\Templates\Renderers\SingleRenderer\NoLinkableText;

class Header extends GenericComponent {

    public function render(){
        // Obtener metadatos y configuración visual
        $journalTitle = $this->config->getMetadata('journal_title');
        $journalData = $this->config->getMetadata('journal_data');
        $doi = $this->config->getMetadata('doi');
        $journalAffiliation = $this->config->getMetadata('journal_affiliation');
        $headerFont = $this->config->getFontConfig('bold', 9);
        $headerColor = $this->config->getColorConfig('accent');
        $doiFont = $this->config->getFontConfig('default', 9);
        $doiColor = $this->config->getColorConfig('accent');

        $journalDataText = ($journalData) ? $journalTitle . ',' . $journalData : $journalTitle;

        if ($journalDataText) {
            NoLinkableText::renderNoLinkableText(
                $this->pdfTemplate,
                $journalDataText,
                10,
                10,
                $headerColor,
                $headerFont,
                'C'
            );
        }   

        if ($doi) {
            $doiUrl = 'https://doi.org/' . $doi;
            LinkableText::renderLinkableText(
                $this->pdfTemplate,
                $doiUrl,
                $doiUrl,
                10,
                $this->pdfTemplate->GetY(),
                $doiColor,
                $doiFont,
                'C'
            );
        }

        /*
        NoLinkableText::renderNoLinkableText(
            $this->pdfTemplate,
            $journalAffiliation,
            10,
            $this->pdfTemplate->GetY(),
            $headerColor,
            $headerFont,
            'C'
        );
        */
    }
}