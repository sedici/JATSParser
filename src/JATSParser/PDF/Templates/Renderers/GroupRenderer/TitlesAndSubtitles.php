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
    public static function renderTitlesAndSubtitles($pdfTemplate, float $x, float $y, Array $titlesConfig, Array $subtitlesConfig, Array $prefixesConfig, $localeKey) {
        $pdfTemplate->SetXY($x, $y);
    
        //print principal prefix (localeKey is used to get the correct principal prefix and unset it from the array)
        if ($prefixesConfig['prefixes_texts'] && $prefixesConfig['prefixes_texts'][$localeKey]) {
            $pdfTemplate->SetFont(
                $prefixesConfig['prefixes_config']['principal_prefix_font']['family'], 
                $prefixesConfig['prefixes_config']['principal_prefix_font']['style'], 
                $prefixesConfig['prefixes_config']['principal_prefix_font']['size']
            );

            $pdfTemplate->SetTextColor(
                $prefixesConfig['prefixes_config']['principal_prefix_color'][0], 
                $prefixesConfig['prefixes_config']['principal_prefix_color'][1], 
                $prefixesConfig['prefixes_config']['principal_prefix_color'][2]
            );

            $pdfTemplate->Write(
                5, 
                $prefixesConfig['prefixes_texts'][$localeKey]
            );
            $pdfTemplate->SetX($pdfTemplate->GetX() + 1.2); // Add some space after the prefix

            unset($prefixesConfig['prefixes_texts'][$localeKey]);
        }

        //print principal title (localeKey is used to get the correct principal title and unset it from the array)
        if ($titlesConfig['titles_texts'] && $titlesConfig['titles_texts'][$localeKey]) {
            $pdfTemplate->SetFont(
                $titlesConfig['titles_config']['principal_title_font']['family'], 
                $titlesConfig['titles_config']['principal_title_font']['style'], 
                $titlesConfig['titles_config']['principal_title_font']['size']
            );
            
            $pdfTemplate->SetTextColor(
                $titlesConfig['titles_config']['principal_title_color'][0], 
                $titlesConfig['titles_config']['principal_title_color'][1], 
                $titlesConfig['titles_config']['principal_title_color'][2]
            );

            $pdfTemplate->Write(
                5, 
                $titlesConfig['titles_texts'][$localeKey]
            );

            unset($titlesConfig['titles_texts'][$localeKey]);
        }

        $pdfTemplate->Ln(7);
 
        //print subtitle title (localeKey is used to get the correct principal subtitle and unset it from the array)
        if ($subtitlesConfig['subtitles_texts'] && $subtitlesConfig['subtitles_texts'][$localeKey]) {
            $pdfTemplate->SetFont(
                $subtitlesConfig['subtitles_config']['principal_subtitle_font']['family'],
                $subtitlesConfig['subtitles_config']['principal_subtitle_font']['style'],
                $subtitlesConfig['subtitles_config']['principal_subtitle_font']['size']
            );
            
            $pdfTemplate->SetTextColor(
                $subtitlesConfig['subtitles_config']['principal_subtitle_color'][0],
                $subtitlesConfig['subtitles_config']['principal_subtitle_color'][1],
                $subtitlesConfig['subtitles_config']['principal_subtitle_color'][2]
            );
            
            $pdfTemplate->Write(
                5, 
                $subtitlesConfig['subtitles_texts'][$localeKey]
            );

            unset($subtitlesConfig['subtitles_texts'][$localeKey]);
        }
        
        $pdfTemplate->Ln(10);

        //print remaining titles and subtitles for all languages if they exist
        foreach ($titlesConfig['titles_texts'] as $language => $title) {
            $prefix = empty($prefixesConfig['prefixes_texts'][$language]) ? '' : $prefixesConfig['prefixes_texts'][$language] . ' ';
            $subtitle = '. ' . $subtitlesConfig['subtitles_texts'][$language] ?? '';

            $text = $prefix . $title . $subtitle;
            $font = $titlesConfig['titles_config']['font']; 

            $pdfTemplate->SetFont(
                $font['family'],
                $font['style'], 
                $font['size']
            );

            $pdfTemplate->SetTextColor(
                $titlesConfig['titles_config']['text_color'][0], 
                $titlesConfig['titles_config']['text_color'][1], 
                $titlesConfig['titles_config']['text_color'][2]
            );

            NoLinkableText::renderNoLinkableText(
                $pdfTemplate,
                $text, 
                $pdfTemplate->GetX(), 
                $pdfTemplate->GetY(), 
                $titlesConfig['titles_config']['text_color'], 
                $font
            );

            $pdfTemplate->Ln(3);
        }

        $pdfTemplate->Ln(5);
    }
}