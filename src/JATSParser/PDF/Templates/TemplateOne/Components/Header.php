<?php namespace JATSParser\PDF\Templates\TemplateOne\Components;

use JATSParser\PDF\Templates\GenericComponent;

    class Header extends GenericComponent {

        public function render(){
            //GET HEADER CONFIGURATION
            $headerConfig = $this->config->getHeaderConfig();
    
            $journalData = $headerConfig['metadata']['journal_title'] . ', ' . $headerConfig['metadata']['journal_data'];
            $doiUrl = 'https://doi.org/' . $headerConfig['metadata']['doi'];
            $journalAffiliation = $headerConfig['metadata']['journal_affiliation'];
            $this->pdfTemplate->createNoClickableText($journalData, 10, 10, $headerConfig['config']['header_data']['text_color'], $headerConfig['config']['header_data']['font'], 'C');
            $this->pdfTemplate->createClickableText($doiUrl, $doiUrl, 10, $this->pdfTemplate->GetY(), $headerConfig['config']['doi']['text_color'], $headerConfig['config']['doi']['font'], 'C');
            $this->pdfTemplate->createNoClickableText($journalAffiliation, 10, $this->pdfTemplate->GetY(), $headerConfig['config']['journal_affiliation']['text_color'], $headerConfig['config']['journal_affiliation']['font'], 'C');
        }
    }