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
        $abstractTitle = TranslationsByKey::getTranslationByKey($translationsConfig, $language, 'abstract') . ':';
        $abstractText = strip_tags($abstractConfig['abstract_texts'][$language]); //Delete HTML tags of abstract
        $keywordsTitle = TranslationsByKey::getTranslationByKey($translationsConfig, $language, 'keywords') . ':';
        $keywords = is_array($keywordsConfig['keywords_texts'][$language]) ? implode(', ', $keywordsConfig['keywords_texts'][$language]) : "";

        $pdfTemplate->SetFont($abstractConfig['abstract_title_font']['family'], $abstractConfig['abstract_title_font']['style'], $abstractConfig['abstract_title_font']['size']);
        $pdfTemplate->SetTextColor($abstractConfig['abstract_title_color'][0], $abstractConfig['abstract_title_color'][1], $abstractConfig['abstract_title_color'][2]);
        $pdfTemplate->Write(5, $abstractTitle);

        $pdfTemplate->SetFont($abstractConfig['abstract_text_font']['family'], $abstractConfig['abstract_text_font']['style'], $abstractConfig['abstract_text_font']['size']);
        $pdfTemplate->SetTextColor($abstractConfig['abstract_text_color'][0], $abstractConfig['abstract_text_color'][1], $abstractConfig['abstract_text_color'][2]);
        $pdfTemplate->SetX($xPosition + $pdfTemplate->GetStringWidth($abstractTitle) + 3);
        $pdfTemplate->Write(5, $abstractText, '', false, 'L');
            
        $pdfTemplate->Ln(7);

        $pdfTemplate->SetFont($keywordsConfig['keywords_title_font']['family'], $keywordsConfig['keywords_title_font']['style'], $keywordsConfig['keywords_title_font']['size']);
        $pdfTemplate->SetTextColor($keywordsConfig['keywords_title_color'][0], $keywordsConfig['keywords_title_color'][1], $keywordsConfig['keywords_title_color'][2]);
        $pdfTemplate->Write(5, $keywordsTitle);

        $pdfTemplate->SetFont($keywordsConfig['keywords_font']['family'], $keywordsConfig['keywords_font']['style'], $keywordsConfig['keywords_font']['size']);
        $pdfTemplate->SetTextColor($keywordsConfig['keywords_color'][0], $keywordsConfig['keywords_color'][1], $keywordsConfig['keywords_color'][2]);
        $pdfTemplate->SetX($xPosition + $pdfTemplate->GetStringWidth($keywordsTitle) + 4);
        $pdfTemplate->Write(5, $keywords);
    }
}