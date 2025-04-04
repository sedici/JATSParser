<?php namespace JATSParser\PDF\Templates\TemplateOne\Components;

use JATSParser\PDF\Templates\GenericComponent;

    class TemplateBody extends GenericComponent {

        public function render() {
            //GET TEMPLATE BODY CONFIGURATION
            $templateBodyConfig = $this->config->getTemplateBodyConfig();

            // ---------------------------------------------------------------------------------------------------------------------------------- //

            //Print institution LOGO
            $this->pdfTemplate->printInstitutionLogo($templateBodyConfig['config']['institution_logo']);
            
            //Print journal LOGO
            $this->pdfTemplate->printJournalLogo($templateBodyConfig['config']['journal_logo']);

            $xPos = $this->pdfTemplate->getImageRBX();
            $xPos = $xPos + 5;
            $yPos = 18;

            //CREATE JOURNAL TITLE
            if ($templateBodyConfig['metadata']['journal_title']) {
                $this->pdfTemplate->createNoClickableText(
                    $templateBodyConfig['metadata']['journal_title'], 
                    $xPos, 
                    $yPos, 
                    $templateBodyConfig['config']['journal_title']['text_color'], 
                    $templateBodyConfig['config']['journal_title']['font']
                );
                $yPos = $yPos + 4;
            }

            // CREATE JOURNAL ISSUE
            if ($templateBodyConfig['metadata']['journal_data']) {
                $this->pdfTemplate->createNoClickableText(
                    $templateBodyConfig['metadata']['journal_data'], 
                    $xPos, 
                    $yPos, 
                    $templateBodyConfig['config']['journal_issue']['text_color'], 
                    $templateBodyConfig['config']['journal_issue']['font']
                );
                $yPos = $yPos + 4;
            }

            //CREATE DOI
            if ($templateBodyConfig['metadata']['doi']) {
                $doiUrl = 'https://doi.org/' . $templateBodyConfig['metadata']['doi']; // Construct complete doi URL.
                $this->pdfTemplate->createClickableText(
                    $doiUrl, 
                    $doiUrl,
                    $xPos, 
                    $yPos, 
                    $templateBodyConfig['config']['doi']['text_color'], 
                    $templateBodyConfig['config']['doi']['font']
                );
                $yPos = $yPos + 4;
            }

            //CREATE ONLINE ISSN
            if ($templateBodyConfig['metadata']['online_issn']) {
                $text = "ISSN " . $templateBodyConfig['metadata']['online_issn'] . ' | ';
                $this->pdfTemplate->createNoClickableText(
                    $text, 
                    $xPos, 
                    $yPos, 
                    $templateBodyConfig['config']['online_issn']['text_color'], 
                    $templateBodyConfig['config']['online_issn']['font']
                );

                $xPos += $this->pdfTemplate->getStringWidth($text);
            }

            //CREATE JOURNAL URL
            if ($templateBodyConfig['metadata']['journal_url']){
                $this->pdfTemplate->createClickableText(
                    $templateBodyConfig['metadata']['journal_url'],
                    $templateBodyConfig['metadata']['journal_url'],
                    $xPos,
                    $yPos,
                    $templateBodyConfig['config']['journal_url']['text_color'],
                    $templateBodyConfig['config']['journal_url']['font']
                );
                $yPos = $yPos + 4;
            }

            if ($templateBodyConfig['metadata']['editorial']) {
                $this->pdfTemplate->createNoClickableText(
                    $templateBodyConfig['metadata']['editorial'],
                    $xPos, 
                    $yPos, 
                    $templateBodyConfig['config']['editorial']['text_color'], 
                    $templateBodyConfig['config']['editorial']['font']
                );
            }

            //Print first line
            $this->pdfTemplate->Line(0, 45, 150, 45);
            
            $this->pdfTemplate->SetLeftMargin(25);
            $this->pdfTemplate->Ln(30);

            // Article titles
            $this->pdfTemplate->createTitlesAndSubtitles(
                $this->pdfTemplate->GetX(), 
                $this->pdfTemplate->GetY(), 
                $this->config->getTitlesConfig(),
                $this->config->getSubtitlesConfig(),
                $templateBodyConfig['metadata']['locale_key']
            );

            $this->pdfTemplate->SetFillColor(0, 0, 0); 
            
            $this->pdfTemplate->createAuthorsData(
                $this->config->getAuthorsConfig(), 
                $this->pdfTemplate->GetX(),
                $this->pdfTemplate->GetY(),
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

            $this->pdfTemplate->createAbstractsAndKeywords(
                $this->config->getKeywordsConfig(),
                $this->config->getAbstractConfig(),
                $templateBodyConfig['metadata']['translations_config'],
                $this->pdfTemplate->GetX(),
                $this->pdfTemplate->GetY(),
                $templateBodyConfig['metadata']['locale_key']
            );

            $this->pdfTemplate->createDates(
                $this->config->getDatesConfig(), 
                $templateBodyConfig['metadata']['translations_config'], 
                $templateBodyConfig['metadata']['locale_key'], 
                $this->pdfTemplate->GetX(), 
                $this->pdfTemplate->GetY()
            );

            $this->pdfTemplate->Ln(25);

            $this->pdfTemplate->SetLeftMargin(15);

        }
    }