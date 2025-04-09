<?php namespace JATSParser\PDF\Templates\Renderers\SingleRenderer;

/**
 * @param string $config -> Configuration object containing template body configuration.
 * @param string $pdfTemplate -> PDF template object used for rendering.
 * @return void
 
 *  This class is responsible for rendering the journal logo in the PDF template.
 *  It checks if a journal logo exists in a specific directory and processes it accordingly.
 *  If the logo is in EPS or SVG format, it uses the appropriate method to render it.
 */

class JournalLogo {

    public static function renderJournalLogo($config, $pdfTemplate): void {
        
        $templateBodyConfig = $config->getTemplateBodyConfig();
        $journalLogoConfig = $templateBodyConfig['config']['journal_logo'];

        //Verify if a journal logo exists in a specific directory:
        $logoPath = null;
        $logoFile = glob($journalLogoConfig['journal_logo_path'] . "logo.*");
        if (!empty($logoFile)) {
            $logoPath = $logoFile[0];
        }
                
        // If the specific journal logo exists in the private files of a journal, process that logo
        if ($logoPath && file_exists($logoPath)) {
            $imgtype = \TCPDF_IMAGES::getImageFileType($logoPath);
            if (($imgtype === 'eps') OR ($imgtype === 'ai')) {
                $pdfTemplate->ImageEps($logoPath, $journalLogoConfig['x_pos'], $journalLogoConfig['y_pos'], $journalLogoConfig['width']);
            } elseif ($imgtype === 'svg') {
                $pdfTemplate->ImageSVG($logoPath, $journalLogoConfig['x_pos'], $journalLogoConfig['y_pos'], $journalLogoConfig['width']);
            } else {
                $pdfTemplate->Image($logoPath, $journalLogoConfig['x_pos'], $journalLogoConfig['y_pos'], $journalLogoConfig['width']);
            }
        }
    }

}