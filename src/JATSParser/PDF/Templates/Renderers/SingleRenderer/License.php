<?php namespace JATSParser\PDF\Templates\Renderers\SingleRenderer;

use JATSParser\PDF\Templates\Renderers\Utils\TranslationsByKey;
use JATSParser\PDF\Templates\Renderers\SingleRenderer\LinkableText;

class License{ 
    public static function renderLicense($pdfTemplate, Array $licenseConfig, Array $translationsConfig, $localeKey, $licenseUrl): void {
        foreach ($licenseConfig['config']['licenses']['links'] as $license => $licenseLink) {
            if ($licenseUrl === $licenseLink) {
                $licenseLogoPath = $licenseConfig['config']['licenses']['logos'][$license]; 
                $pdfTemplate->Image(
                    $licenseLogoPath, 
                    $pdfTemplate->GetX(), 
                    $pdfTemplate->GetY() - 1, 
                    $licenseConfig['config']['licenses']['logo_width'], 
                    $licenseConfig['config']['licenses']['logo_height'], 
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
                    $licenseConfig['config']['licenses']['text_color'], 
                    $licenseConfig['config']['licenses']['font']
                );
            }
        }
    }
    
}