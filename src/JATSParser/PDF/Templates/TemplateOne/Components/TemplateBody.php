<?php namespace JATSParser\PDF\Templates\TemplateOne\Components;

use JATSParser\PDF\Templates\GenericComponent;

use JATSParser\PDF\Templates\Renderers\SingleRenderer\JournalLogo;
use JATSParser\PDF\Templates\Renderers\SingleRenderer\InstitutionLogo;
use JATSParser\PDF\Templates\Renderers\SingleRenderer\NoLinkableText;
use JATSParser\PDF\Templates\Renderers\SingleRenderer\LinkableText;
use JATSParser\PDF\Templates\Renderers\GroupRenderer\TitlesAndSubtitles;
use JATSParser\PDF\Templates\Renderers\GroupRenderer\AuthorsData;
use JATSParser\PDF\Templates\Renderers\GroupRenderer\AbstractAndKeywords;
use JATSParser\PDF\Templates\Renderers\SingleRenderer\Dates;
use JATSParser\PDF\Templates\Renderers\SingleRenderer\License;

    class TemplateBody extends GenericComponent {

        public function render() {
            //GET TEMPLATE BODY CONFIGURATION
            $templateBodyConfig = $this->config->getTemplateBodyConfig();

            // ---------------------------------------------------------------------------------------------------------------------------------- //

            InstitutionLogo::renderInstitutionLogo($this->config, $this->pdfTemplate);

            JournalLogo::renderJournalLogo($this->config, $this->pdfTemplate);

            $xPos = $this->pdfTemplate->getImageRBX();
            $xPos = $xPos + 5;
            $yPos = 18;

            //RENDER JOURNAL TITLE TEXT
            if ($templateBodyConfig['metadata']['journal_title']) {
                NoLinkableText::renderNoLinkableText(
                    $this->pdfTemplate, 
                    $templateBodyConfig['metadata']['journal_title'], 
                    $xPos, 
                    $yPos, 
                    $templateBodyConfig['config']['journal_title']['text_color'], 
                    $templateBodyConfig['config']['journal_title']['font']
                );
                $yPos = $yPos + 4;
            }

            //RENDER COMPLETE JOURNAL DATA TEXT. INCLUDES VOLUME, ISSUE, YEAR AND TITLE
            if ($templateBodyConfig['metadata']['journal_data']) {
                NoLinkableText::renderNoLinkableText(
                    $this->pdfTemplate,
                    $templateBodyConfig['metadata']['journal_data'],
                    $xPos,
                    $yPos,
                    $templateBodyConfig['config']['journal_issue']['text_color'],
                    $templateBodyConfig['config']['journal_issue']['font']
                );
                $yPos = $yPos + 4;
            }

            //RENDER DOI TEXT
            if ($templateBodyConfig['metadata']['doi']) {
                $doiUrl = 'https://doi.org/' . $templateBodyConfig['metadata']['doi']; // Construct complete doi URL.
                LinkableText::renderLinkableText(
                    $this->pdfTemplate, 
                    $doiUrl, 
                    $doiUrl, 
                    $xPos, 
                    $yPos, 
                    $templateBodyConfig['config']['doi']['text_color'], 
                    $templateBodyConfig['config']['doi']['font']
                );
                $yPos = $yPos + 4;
            }

            //RENDER ONLINE ISSN TEXT
            if ($templateBodyConfig['metadata']['online_issn']) {
                $text = "ISSN " . $templateBodyConfig['metadata']['online_issn'] . ' | ';
                NoLinkableText::renderNoLinkableText(
                    $this->pdfTemplate,
                    $text,
                    $xPos,
                    $yPos,
                    $templateBodyConfig['config']['online_issn']['text_color'],
                    $templateBodyConfig['config']['online_issn']['font']
                );
                $yPos = $yPos + 4;
            }

            //CREATE JOURNAL URL
            if ($templateBodyConfig['metadata']['journal_url']){
                LinkableText::renderLinkableText(
                    $this->pdfTemplate, 
                    $templateBodyConfig['metadata']['journal_url'], 
                    $templateBodyConfig['metadata']['journal_url'], 
                    $xPos, 
                    $yPos, 
                    $templateBodyConfig['config']['journal_url']['text_color'], 
                    $templateBodyConfig['config']['journal_url']['font']
                );
                $yPos = $yPos + 1;
            }

            /*
             if ($templateBodyConfig['metadata']['editorial']) {
                NoLinkableText::renderNoLinkableText(
                    $this->pdfTemplate, 
                    $templateBodyConfig['metadata']['editorial'], 
                    $xPos, 
                    $yPos, 
                    $templateBodyConfig['config']['editorial']['text_color'], 
                    $templateBodyConfig['config']['editorial']['font']
                );
                $yPos = $yPos + 4;
            }
            */

            if ($templateBodyConfig['metadata']['date_submitted'] || $templateBodyConfig['metadata']['date_accepted'] || $templateBodyConfig['metadata']['date_published']) {
                Dates::renderDates(
                    $this->pdfTemplate, 
                    $this->config->getDatesConfig(), 
                    $templateBodyConfig['metadata']['translations_config'], 
                    $templateBodyConfig['metadata']['locale_key'], 
                    $xPos, 
                    $yPos
                );
            }
 
            //Print first line
            $this->pdfTemplate->Line(0, 45, 150, 45);
            $this->pdfTemplate->SetLeftMargin(25);
            $this->pdfTemplate->Ln(30);

            //Render article titles and subtitles
            TitlesAndSubtitles::renderTitlesAndSubtitles(
                $this->pdfTemplate, 
                $this->pdfTemplate->GetX(), 
                $this->pdfTemplate->GetY(), 
                $this->config->getTitlesConfig(),
                $this->config->getSubtitlesConfig(),
                $templateBodyConfig['metadata']['locale_key']
            );

            $this->pdfTemplate->SetFillColor(0, 0, 0); 

            AuthorsData::renderAuthorsData(
                $this->pdfTemplate, 
                $this->pdfTemplate->GetX(), 
                $this->pdfTemplate->GetY(), 
                $this->config->getAuthorsConfig(),
                $templateBodyConfig['metadata']['locale_key']
            );

            $this->pdfTemplate->Ln(5);
            //Print second line
            $this->pdfTemplate->Line(
                $this->pdfTemplate->GetX(), 
                $this->pdfTemplate->GetY(), 
                $this->pdfTemplate->GetX() + 155, 
                $this->pdfTemplate->GetY()
            );
            
            $this->pdfTemplate->SetRightMargin(30);
            $this->pdfTemplate->Ln(5);

            AbstractAndKeywords::renderAbstractsAndKeywords(
                $this->pdfTemplate, 
                $this->config->getKeywordsConfig(), 
                $this->config->getAbstractConfig(), 
                $templateBodyConfig['metadata']['translations_config'], 
                $this->pdfTemplate->GetX(), 
                $this->pdfTemplate->GetY(), 
                $templateBodyConfig['metadata']['locale_key']
            );

            $this->pdfTemplate->Ln(5);

            License::renderLicense(
                $this->pdfTemplate,
                $templateBodyConfig,
                $templateBodyConfig['metadata']['translations_config'], 
                $templateBodyConfig['metadata']['locale_key'], 
                $templateBodyConfig['metadata']['license_url']
            );

            $this->pdfTemplate->Ln(5);

            /*
            if ($licenseUrl) {
                License::renderLicense($this->pdfTemplate, $footerConfig, $translationsConfig, $localeKey, $licenseUrl);
            }
            */

            $this->pdfTemplate->Ln(25);

            $this->pdfTemplate->SetLeftMargin(15);

        }
    }