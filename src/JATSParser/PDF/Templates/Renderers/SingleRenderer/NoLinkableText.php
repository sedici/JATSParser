<?php namespace JATSParser\PDF\Templates\Renderers\SingleRenderer;

/**
 * @param $pdfTemplate -> PDF template object (for example TemplateOne)
 * @param String $text -> Text to be rendered
 * @param float $x -> X position
 * @param float $y -> Y position
 * @param Array $textColor -> Text color in RGB format
 * @param Array $textFont -> Text font configuration
 * @param String $align -> Text alignment (default is 'L' for left)
 * 
 * This class is used to render text in a PDF template without any link functionality.
 * It sets the position, color, font, and alignment of the text.
 * The text is rendered using the MultiCell method of the PDF template.
 * The text color is reset to black after rendering.
 */

class NoLinkableText {
    public static function renderNoLinkableText($pdfTemplate, String $text, float $x, float $y, Array $textColor, Array $textFont, String $align = 'L'): void{
        $pdfTemplate->SetXY($x, $y);
        $pdfTemplate->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
        $pdfTemplate->SetFont($textFont['family'], $textFont['style'], $textFont['size']);
        $pdfTemplate->MultiCell(0, 5, $text, 0, $align);
        $pdfTemplate->SetTextColor(0, 0, 0);
    }
}