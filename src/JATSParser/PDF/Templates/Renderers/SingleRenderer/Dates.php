<?php namespace JATSParser\PDF\Templates\Renderers\SingleRenderer;

use JATSParser\PDF\Templates\Renderers\Utils\TranslationsByKey;

class Dates {

    public static function renderDates($pdfTemplate, array $datesConfig, Array $translationsConfig, $localeKey, $xPosition, $yPosition): void {
        $pdfTemplate->SetXY($xPosition, $yPosition);
        
        $acceptedText = TranslationsByKey::getTranslationByKey($translationsConfig, $localeKey, 'accepted') . ': ' . $datesConfig['date_accepted'];
        $submittedText = TranslationsByKey::getTranslationByKey($translationsConfig, $localeKey, 'received') . ': ' . $datesConfig['date_submitted'];
        
        if ($datesConfig['date_published']) {
            $publishedText = TranslationsByKey::getTranslationByKey($translationsConfig, $localeKey, 'published') . ': ' . $datesConfig['date_published'];
        } else {
            $publishedText = '';
        }

        $datesText = trim(implode(' - ', array_filter([$submittedText, $acceptedText, $publishedText])));

        $pdfTemplate->SetFont($datesConfig['dates_font']['family'], $datesConfig['dates_font']['style'], $datesConfig['dates_font']['size']);
        $pdfTemplate->SetTextColor($datesConfig['dates_color'][0], $datesConfig['dates_color'][1], $datesConfig['dates_color'][2]);
        $pdfTemplate->Ln(10);
        $pdfTemplate->Cell(0, 10, $datesText, 0, 1, 'L');
        $pdfTemplate->SetTextColor(0, 0, 0);
    }

}