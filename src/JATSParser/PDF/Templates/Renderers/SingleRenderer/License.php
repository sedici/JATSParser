<?php namespace JATSParser\PDF\Templates\Renderers\SingleRenderer;

use JATSParser\PDF\Templates\Renderers\Utils\TranslationsByKey;
use JATSParser\PDF\Templates\Renderers\SingleRenderer\LinkableText;

class License{ 
    public static function renderLicense($pdfTemplate, Array $licenseConfig, Array $translationsConfig, $localeKey, $licenseUrl): void {
        foreach ($licenseConfig['links'] as $license => $licenseLink) {
            if ($licenseUrl === $licenseLink) {
                $licenseLogoPath = $licenseConfig['logos'][$license]; 
                $pdfTemplate->Image(
                    $licenseLogoPath, 
                    $pdfTemplate->GetX(), 
                    $pdfTemplate->GetY() - 1, 
                    $licenseConfig['logo_width'], 
                    $licenseConfig['logo_height'], 
                    '', 
                    $licenseLink, 
                    'L'
                );

                $xPosition = $pdfTemplate->getImageRBX() + 2;
                $translationText = TranslationsByKey::getTranslationByKey($translationsConfig, $localeKey, 'license_text') . ' ' . $license;

                LinkableText::renderLinkableText(
                    $pdfTemplate, 
                    $licenseLink, 
                    $translationText, 
                    $xPosition, 
                    $pdfTemplate->GetY() + 0.5, 
                    $licenseConfig['text_color'], 
                    $licenseConfig['font']
                );
            }
        }
    }
    
}