<?php namespace JATSParser\PDF\Templates\Renderers\SingleRenderer;

class ClickableOrcidLogo {
    public static function renderClickableOrcidLogo($pdfTemplate, float $x, float $y, $size, $orcidLink, $pluginPath): void {
        $orcidLogoPath = $pluginPath . '/JATSParser/logo/orcid.png';
        if (file_exists($orcidLogoPath)) {
            $pdfTemplate->Image($orcidLogoPath, $x, $y, $size, $size, '', $orcidLink);
        }
    }
}