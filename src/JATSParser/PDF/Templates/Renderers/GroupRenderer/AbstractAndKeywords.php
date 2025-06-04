<?php namespace JATSParser\PDF\Templates\Renderers\GroupRenderer;

use JATSParser\PDF\Templates\Renderers\Utils\TranslationsByKey;

class AbstractAndKeywords {

    public static function renderAbstractsAndKeywords($pdfTemplate, Array $keywordsConfig, Array $abstractConfig, Array $translationsConfig, float $xPosition, float $yPosition, $localeKey) {
        $pdfTemplate->SetXY($xPosition, $yPosition);
    
        // First print the abstract and keywords for the localeKey (original language)
        if (isset($abstractConfig['abstract_texts'][$localeKey])) {
            self::printAbstractAndKeywords($pdfTemplate, $keywordsConfig, $abstractConfig, $translationsConfig, $xPosition, $localeKey);
            $pdfTemplate->Ln(10);
            unset($abstractConfig['abstract_texts'][$localeKey]);
            unset($keywordsConfig['keywords_texts'][$localeKey]);
        }

        foreach ($abstractConfig['abstract_texts'] as $language => $abstract) {
            self::printAbstractAndKeywords($pdfTemplate, $keywordsConfig, $abstractConfig, $translationsConfig, $xPosition, $language);
            $pdfTemplate->Ln(10);
        }
    }


    // Prints the abstract and keywords in one language.
    public static function printAbstractAndKeywords($pdfTemplate, Array $keywordsConfig, Array $abstractConfig, Array $translationsConfig, float $xPosition, $language) {
        // Check if abstract exists and is not empty
        $hasAbstractContent = isset($abstractConfig['abstract_texts'][$language]) && !empty(trim(strip_tags($abstractConfig['abstract_texts'][$language])));
        
        // Check if keywords exist and are not empty
        $hasKeywordsContent = isset($keywordsConfig['keywords_texts'][$language]) && 
                             is_array($keywordsConfig['keywords_texts'][$language]) && 
                             !empty($keywordsConfig['keywords_texts'][$language]);
        
        // Render abstract section if content exists
        if ($hasAbstractContent) {
            $abstractTitle = trim(TranslationsByKey::getTranslationByKey($translationsConfig, $language, 'abstract')) . ' | ';
            $abstractText = trim(strip_tags($abstractConfig['abstract_texts'][$language]));
            
            $pdfTemplate->SetFont($abstractConfig['abstract_title_font']['family'], $abstractConfig['abstract_title_font']['style'], $abstractConfig['abstract_title_font']['size']);
            $pdfTemplate->SetTextColor($abstractConfig['abstract_title_color'][0], $abstractConfig['abstract_title_color'][1], $abstractConfig['abstract_title_color'][2]);
            $pdfTemplate->Write(5, $abstractTitle);

            $pdfTemplate->SetFont($abstractConfig['abstract_text_font']['family'], $abstractConfig['abstract_text_font']['style'], $abstractConfig['abstract_text_font']['size']);
            $pdfTemplate->SetTextColor($abstractConfig['abstract_text_color'][0], $abstractConfig['abstract_text_color'][1], $abstractConfig['abstract_text_color'][2]);
            $pdfTemplate->Write(5, $abstractText, '', false, 'L');
            
            // Only add line break if we're going to render keywords next
            if ($hasKeywordsContent) {
                $pdfTemplate->Ln(7);
            }
        }
        
        // Render keywords section if content exists
        if ($hasKeywordsContent) {
            $keywordsTitle = trim(TranslationsByKey::getTranslationByKey($translationsConfig, $language, 'keywords')) . ' | ';
            $keywords = trim(implode(', ', $keywordsConfig['keywords_texts'][$language]));

            $pdfTemplate->SetFont($keywordsConfig['keywords_title_font']['family'], $keywordsConfig['keywords_title_font']['style'], $keywordsConfig['keywords_title_font']['size']);
            $pdfTemplate->SetTextColor($keywordsConfig['keywords_title_color'][0], $keywordsConfig['keywords_title_color'][1], $keywordsConfig['keywords_title_color'][2]);
            $pdfTemplate->Write(5, $keywordsTitle);

            $pdfTemplate->SetFont($keywordsConfig['keywords_font']['family'], $keywordsConfig['keywords_font']['style'], $keywordsConfig['keywords_font']['size']);
            $pdfTemplate->SetTextColor($keywordsConfig['keywords_color'][0], $keywordsConfig['keywords_color'][1], $keywordsConfig['keywords_color'][2]);
            $pdfTemplate->Write(5, $keywords);
        }
    }
}