<?php namespace JATSParser\PDF\Templates\Renderers\GroupRenderer;

use JATSParser\PDF\Templates\Renderers\SingleRenderer\ClickableOrcidLogo;

class AuthorsData {

        public static function renderAuthorsData($pdfTemplate, float $xPosition, float $yPosition, Array $authorsConfig, $localeKey){
            $pdfTemplate->SetXY($xPosition, $yPosition);
            if (count($authorsConfig['authors_data']) > 0) {
                foreach ($authorsConfig['authors_data'] as $author) {
                    
                    // Author's bold name
                    $pdfTemplate->SetFont(
                        $authorsConfig['authors_config']['fullname_font']['family'], 
                        $authorsConfig['authors_config']['fullname_font']['style'], 
                        $authorsConfig['authors_config']['fullname_font']['size']
                    ); 
                    $pdfTemplate->SetTextColor(
                        $authorsConfig['authors_config']['fullname_text_color'][0],
                        $authorsConfig['authors_config']['fullname_text_color'][1], 
                        $authorsConfig['authors_config']['fullname_text_color'][2]
                    );
                    $authorName = htmlspecialchars($author->getGivenName($localeKey)) . ' ' . htmlspecialchars($author->getFamilyName($localeKey));
                    $pdfTemplate->MultiCell(0, 0, $authorName, 0, 'L', false, 1, '', '', true);
    
                    // ORCID CLICKABLE LOGO
                    if (htmlspecialchars($author->getOrcid())) {
                        $xLogo = $pdfTemplate->GetX() + $pdfTemplate->GetStringWidth($authorName) + 2;
                        $yLogo = $pdfTemplate->GetY() - 3.65;
                        ClickableOrcidLogo::renderClickableOrcidLogo($pdfTemplate, $xLogo, $yLogo, 3, $author->getOrcid());	
                    }
    
                    // Email
                    if ($author->getEmail()) {
                        $email = htmlspecialchars($author->getEmail());
                        $pdfTemplate->SetFont(
                            $authorsConfig['authors_config']['email_font']['family'], 
                            $authorsConfig['authors_config']['email_font']['style'], 
                            $authorsConfig['authors_config']['email_font']['size']
                        );
                        $pdfTemplate->SetTextColor(
                            $authorsConfig['authors_config']['email_text_color'][0], 
                            $authorsConfig['authors_config']['email_text_color'][1], 
                            $authorsConfig['authors_config']['email_text_color'][2]
                        );
                        $pdfTemplate->Write(5, $email, 'mailto:' . $email);
                        $pdfTemplate->Ln(5);
                    }
    
                    // Affiliation
                    if ($author->getAffiliation($localeKey)) {
                        $affiliation = htmlspecialchars($author->getAffiliation($localeKey));
                        $pdfTemplate->SetFont(
                            $authorsConfig['authors_config']['affiliation_font']['family'], 
                            $authorsConfig['authors_config']['affiliation_font']['style'], 
                            $authorsConfig['authors_config']['affiliation_font']['size']
                        );
                        $pdfTemplate->SetTextColor(
                            $authorsConfig['authors_config']['affiliation_text_color'][0], 
                            $authorsConfig['authors_config']['affiliation_text_color'][1], 
                            $authorsConfig['authors_config']['affiliation_text_color'][2]
                        );
                        $pdfTemplate->MultiCell(0, 0, $affiliation, 0, 'L', false, 1, '', '', true);
                    }
    
                    // Space between authors
                    $pdfTemplate->SetTextColor(0, 0, 0);
                    $pdfTemplate->Ln(3); 
                }
            }	
        }
    }