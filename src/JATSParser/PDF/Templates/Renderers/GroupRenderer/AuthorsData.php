<?php namespace JATSParser\PDF\Templates\Renderers\GroupRenderer;

use JATSParser\PDF\Templates\Renderers\SingleRenderer\ClickableOrcidLogo;

class AuthorsData {

        public static function renderAuthorsData($pdfTemplate, float $xPosition, float $yPosition, Array $authorsConfig, $localeKey){
            $pdfTemplate->SetXY($xPosition, $yPosition);
            if (count($authorsConfig['authors_data']) > 0) {
                foreach ($authorsConfig['authors_data'] as $author) {
                    
                    // Author's bold name - set font first
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
                    
                    // Store current position
                    $currentX = $pdfTemplate->GetX();
                    $currentY = $pdfTemplate->GetY();
                    
                    // ORCID CLICKABLE LOGO (before author name)
                    if (htmlspecialchars($author->getOrcid())) {
                        ClickableOrcidLogo::renderClickableOrcidLogo($pdfTemplate, $currentX, $currentY, 5, $author->getOrcid());
                        // Move position to the right of the logo for the author name
                        $pdfTemplate->SetXY($currentX + 5, $currentY); // Adjust the 5 value as needed for spacing
                    }
                    
                    // Now render the author name
                    $pdfTemplate->MultiCell(0, 0, $authorName, 0, 'L', false, 1, '', '', true);
    
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
    
                    // Affiliation with Country
                    if ($author->getAffiliation($localeKey)) {
                        $affiliationText = htmlspecialchars($author->getAffiliation($localeKey));
                        
                        // Append country if available
                        if ($author->getCountryLocalized($localeKey)) {
                            $country = htmlspecialchars($author->getCountryLocalized($localeKey));
                            $affiliationText .= ', ' . $country;
                        }
                        
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
                        $pdfTemplate->MultiCell(0, 0, $affiliationText, 0, 'L', false, 1, '', '', true);
                    }
                    
                    // Space between authors
                    $pdfTemplate->SetTextColor(0, 0, 0);
                    $pdfTemplate->Ln(3); 
                }
            }	
        }
    }