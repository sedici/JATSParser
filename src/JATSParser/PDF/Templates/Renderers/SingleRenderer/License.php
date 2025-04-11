<?php namespace JATSParser\PDF\Templates\Renderers\SingleRenderer;

use JATSParser\PDF\Templates\Renderers\Utils\TranslationsByKey;
use JATSParser\PDF\Templates\Renderers\SingleRenderer\LinkableText;

class License{
    public static function renderLicense($pdfTemplate, Array $footerConfig, Array $translationsConfig, $localeKey, $licenseUrl): void {
        foreach ($footerConfig['config']['licenses']['links'] as $license => $licenseLink) {
            if ($licenseUrl === $licenseLink) {
                $licenseLogoPath = $footerConfig['config']['licenses']['logos'][$license]; 
                $pdfTemplate->Image(
                    $licenseLogoPath, 
                    $pdfTemplate->GetX(), 
                    $pdfTemplate->GetY() - 1, 
                    $footerConfig['config']['licenses']['logo_width'], 
                    $footerConfig['config']['licenses']['logo_height'], 
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
                    $footerConfig['config']['licenses']['text_color'], 
                    $footerConfig['config']['licenses']['font']
                );
            }
        }
    }
    
}