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
        // Obtain metadata and visual configuration
        $journalTitle = $this->config->getMetadata('journal_title');
        $journalData = $this->config->getMetadata('journal_data');
        $doi = $this->config->getMetadata('doi');
        $onlineIssn = $this->config->getMetadata('online_issn');
        $journalUrl = $this->config->getMetadata('journal_url');
        $editorial = $this->config->getMetadata('editorial');
        $dateSubmitted = $this->config->getMetadata('date_submitted');
        $dateAccepted = $this->config->getMetadata('date_accepted');
        $datePublished = $this->config->getMetadata('date_published');
        $translationsConfig = $this->config->getMetadata('translations_config');
        $articleLocaleKey = $this->config->getMetadata('article_locale_key');
        $licenseUrl = $this->config->getMetadata('license_url');
        $publicationPages = $this->config->getMetadata('publication_pages');
        $issueVolume = $this->config->getMetadata('issue_volume');
        $issueNumber = $this->config->getMetadata('issue_number');
        $issueYear = $this->config->getMetadata('issue_year');
        $sectionTitle = $this->config->getMetadata('section_title');


        $journalTitleFont = $this->config->getFontConfig('calibri');
        $journalTitleColor = $this->config->getColorConfig('black');
        $journalIssueFont = $this->config->getFontConfig('calibri');
        $journalIssueColor = $this->config->getColorConfig('black');
        $doiFont = $this->config->getFontConfig('calibri');
        $doiColor = $this->config->getColorConfig('primary');
        $onlineIssnFont = $this->config->getFontConfig('calibri');
        $onlineIssnColor = $this->config->getColorConfig('black');
        $journalUrlFont = $this->config->getFontConfig('calibri');
        $journalUrlColor = $this->config->getColorConfig('primary');
        $editorialFont = $this->config->getFontConfig('calibri');
        $editorialColor = $this->config->getColorConfig('black');
        $urlColor = $this->config->getColorConfig('url');

         // Render logos

        InstitutionLogo::renderInstitutionLogo($this->config, $this->pdfTemplate);
        $logoExists = JournalLogo::renderJournalLogo($this->config, $this->pdfTemplate);

        if ($logoExists) {
            $xPos = $this->pdfTemplate->getImageRBX();
            $xPos = $xPos + 5;
            $yPos = 18;
        } else {
            $xPos = 15;
            $yPos = 18;
        }

        // RENDER JOURNAL TITLE TEXT
        if ($journalTitle) {
            NoLinkableText::renderNoLinkableText(
                $this->pdfTemplate,
                $journalTitle,
                $xPos,
                $yPos,
                $journalTitleColor,
                $journalTitleFont
            );
            $yPos = $yPos + 4;
        }

        // RENDER COMPLETE JOURNAL DATA TEXT
        if ($issueVolume || $issueNumber || $issueYear) {
            $journalDataText = '';
            if ($issueVolume) {
                $journalDataText .= 'Vol. ' . $issueVolume . ', ';
            }
            if ($issueNumber) {
                $journalDataText .= 'Núm. ' . $issueNumber . ', ';
            }
            if ($publicationPages) {
                $journalDataText .= $publicationPages . ', ';
            }
            if ($sectionTitle) {
                $journalDataText .= $sectionTitle . ', ';
            }
            if ($issueYear) {
                $journalDataText .=  $issueYear;
            }
            NoLinkableText::renderNoLinkableText(
                $this->pdfTemplate,
                $journalDataText,
                $xPos,
                $yPos,
                $journalIssueColor,
                $journalIssueFont
            );
            $yPos = $yPos + 4;
        }

        // RENDER DOI TEXT
        if ($doi) {
            $doiUrl = 'https://doi.org/' . $doi;
            LinkableText::renderLinkableText(
                $this->pdfTemplate,
                $doiUrl,
                $doiUrl,
                $xPos,
                $yPos,
                $urlColor,
                $doiFont
            );
            $yPos = $yPos + 4;
        }

        // RENDER ONLINE ISSN TEXT AND/OR JOURNAL URL
        $currentXPos = $xPos; // Initial X position

        // Primero verificamos si hay ISSN y lo imprimimos
        // First, we check if there is an ISSN and print it
        if ($onlineIssn) {
            $text = "ISSN " . $onlineIssn . ' | ';
            NoLinkableText::renderNoLinkableText(
                $this->pdfTemplate,
                $text,
                $currentXPos,
                $yPos,
                $onlineIssnColor,
                $onlineIssnFont
            );
            // Update the X position for the possible URL
            $currentXPos += $this->pdfTemplate->GetStringWidth($text);
        }
        
        // Verificamos si hay URL y la imprimimos en la posición actual
        if ($journalUrl) {
            LinkableText::renderLinkableText(
                $this->pdfTemplate,
                $journalUrl,
                $journalUrl,
                $currentXPos,
                $yPos,
                $urlColor,
                $journalUrlFont
            );
        }
        
        // Solo incrementamos Y si se imprimió algo (ISSN o URL o ambos)
        if ($onlineIssn || $journalUrl) {
            $yPos = $yPos + 1; // Reducido de 4 a 2 para disminuir el espacio
        }

        /*
        if ($editorial) {
            NoLinkableText::renderNoLinkableText(
                $this->pdfTemplate,
                $editorial,
                $xPos,
                $yPos,
                $editorialColor,
                $editorialFont
            );
            $yPos = $yPos + 4;
        }
        */

        if ($dateSubmitted || $dateAccepted || $datePublished) {
            Dates::renderDates(
                $this->pdfTemplate,
                $this->config->getDatesConfig(),
                $translationsConfig,
                $articleLocaleKey,
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
            $this->config->getPrefixesConfig(),
            $articleLocaleKey
        );

        $this->pdfTemplate->SetFillColor(0, 0, 0);

        AuthorsData::renderAuthorsData(
            $this->pdfTemplate,
            $this->pdfTemplate->GetX(),
            $this->pdfTemplate->GetY(),
            $this->config->getAuthorsConfig(),
            $articleLocaleKey
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
            $translationsConfig,
            $this->pdfTemplate->GetX(),
            $this->pdfTemplate->GetY(),
            $articleLocaleKey
        );

        $this->pdfTemplate->Ln(5);

        License::renderLicense(
            $this->pdfTemplate,
            $this->config->getLicenseConfig(),
            $translationsConfig,
            $articleLocaleKey,
            $licenseUrl
        );

        $this->pdfTemplate->Ln(5);
        $this->pdfTemplate->Ln(25);
        $this->pdfTemplate->SetLeftMargin(15);
    }
}