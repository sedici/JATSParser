<?php namespace JATSParser\PDF\Templates\Renderers\SingleRenderer;

/**
 * @param $config -> The configuration object containing TemplateBody settings.
 * @param $pdfTemplate -> The PDF template object where the logo will be rendered.
 * @return void
 * 
 * This class is responsible for rendering the institution logo in the PDF template.
 * It checks if the logo file exists in the specified path and then renders it
 * at the specified position with the specified width.
 */

class InstitutionLogo {

    public static function renderInstitutionLogo($config, $pdfTemplate): void {
        $templateBodyConfig = $config->getTemplateBodyConfig();

        $institutionLogoConfig = $templateBodyConfig['config']['institution_logo'];

        $logoFile = glob($institutionLogoConfig['institution_logo_path'] . "institution.*");
        if (!empty($logoFile)) {
            $logoPath = $logoFile[0];
        }

        if ($logoPath && file_exists($logoPath)) {
            $pdfTemplate->Image(
                $logoPath, 
                $institutionLogoConfig['x_pos'], 
                $institutionLogoConfig['y_pos'], 
                $institutionLogoConfig['width']
            );
        }
    }

}