<?php namespace JATSParser\PDF\Templates\Renderers\GroupRenderer;

use JATSParser\PDF\Templates\Renderers\SingleRenderer\NoLinkableText;

class TitlesAndSubtitles {
    /* 
    * Renders titles and subtitles in a PDF template one after another
    * with specific configurations for fonts and colors.
    *
    * @param object $pdfTemplate The PDF template object.
    * @param float $x The x-coordinate for positioning.
    * @param float $y The y-coordinate for positioning.
    * @param array $titlesConfig Configuration for titles.
    * @param array $subtitlesConfig Configuration for subtitles.
    * @param string $localeKey The locale key for the titles and subtitles.
    * @return void
    */
    public static function renderTitlesAndSubtitles($pdfTemplate, float $x, float $y, Array $titlesConfig, Array $subtitlesConfig, $localeKey) {
        $pdfTemplate->SetXY($x, $y);
        
        if ($titlesConfig['titles_texts'] && $titlesConfig['titles_texts'][$localeKey]) {
            $pdfTemplate->SetFont($titlesConfig['titles_config']['principal_title_font']['family'], $titlesConfig['titles_config']['principal_title_font']['style'], $titlesConfig['titles_config']['principal_title_font']['size']);
            $pdfTemplate->SetTextColor($titlesConfig['titles_config']['principal_title_color'][0], $titlesConfig['titles_config']['principal_title_color'][1], $titlesConfig['titles_config']['principal_title_color'][2]);
            $pdfTemplate->Write(5, $titlesConfig['titles_texts'][$localeKey]);
            unset($titlesConfig['titles_texts'][$localeKey]);
        }

        $pdfTemplate->Ln(7);
 
        if ($subtitlesConfig['subtitles_texts'] && $subtitlesConfig['subtitles_texts'][$localeKey]) {
            $pdfTemplate->SetFont($subtitlesConfig['subtitles_config']['principal_subtitle_font']['family'], $subtitlesConfig['subtitles_config']['principal_subtitle_font']['style'], $subtitlesConfig['subtitles_config']['principal_subtitle_font']['size']);
            $pdfTemplate->SetTextColor($subtitlesConfig['subtitles_config']['principal_subtitle_color'][0], $subtitlesConfig['subtitles_config']['principal_subtitle_color'][1], $subtitlesConfig['subtitles_config']['principal_subtitle_color'][2]);
            $pdfTemplate->Write(5, $subtitlesConfig['subtitles_texts'][$localeKey]);
            unset($subtitlesConfig['subtitles_texts'][$localeKey]);
        }
        
        $pdfTemplate->Ln(10);

        foreach ($titlesConfig['titles_texts'] as $language => $title) {
            $text = $title . '. ' . $subtitlesConfig['subtitles_texts'][$language];
            $font = $titlesConfig['titles_config']['font']; 
            $pdfTemplate->SetFont($font['family'], $font['style'], $font['size']);
            $pdfTemplate->SetTextColor($titlesConfig['titles_config']['text_color'][0], $titlesConfig['titles_config']['text_color'][1], $titlesConfig['titles_config']['text_color'][2]);
            NoLinkableText::renderNoLinkableText($pdfTemplate, $text, $pdfTemplate->GetX(), $pdfTemplate->GetY(), $titlesConfig['titles_config']['text_color'], $font);
            $pdfTemplate->Ln(3);
        }

        $pdfTemplate->Ln(5);
    }
}