<?php namespace JATSParser\PDF\Templates\Renderers\SingleRenderer;

/**
 * @param $pdfTemplate -> PDF template object (for example TemplateOne)
 * @param String $url -> URL to be linked
 * @param String $visibleText -> Text to be displayed
 * @param float $xPosition -> X position
 * @param float $yPosition -> Y position
 * @param Array $textColor -> Text color in RGB format
 * @param Array $textFont -> Text font configuration
 * @param string $align -> Text alignment (default is '')
 * @return void 
 
 * @description This class is used to render a linkable text in the PDF template. 
 * It sets the position, color, font, and alignment of the text.
 * The text is rendered using the Write method of the PDF template.
 * The text color is reset to black after rendering.
 * The URL is passed as a parameter to the Write method, which makes the text clickable.
*/

class LinkableText{
    public static function renderLinkableText($pdfTemplate, $url, $visibleText, float $xPosition, float $yPosition, Array $textColor, Array $textFont, $align = '') {
        $pdfTemplate->SetXY($xPosition, $yPosition);
        $pdfTemplate->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
        $pdfTemplate->SetFont($textFont['family'], $textFont['style'], $textFont['size']);
        $pdfTemplate->Write(0, $visibleText, $url, false, $align);
        $pdfTemplate->SetTextColor(0, 0, 0);
    }
}