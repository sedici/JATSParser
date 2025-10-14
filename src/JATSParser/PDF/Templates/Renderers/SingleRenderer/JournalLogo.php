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

    public static function renderJournalLogo($config, $pdfTemplate): bool {
        $journalLogoConfig = $config->getLogoConfig('journal_logo');

        $logoPath = null;
        $logoFile = glob($journalLogoConfig['path'] . "logo.*");
        if (!empty($logoFile)) {
            $logoPath = $logoFile[0];
        }
        if ($logoPath && file_exists($logoPath)) {
            $imgtype = \TCPDF_IMAGES::getImageFileType($logoPath);
            if (($imgtype === 'eps') OR ($imgtype === 'ai')) {
                $pdfTemplate->ImageEps($logoPath, $journalLogoConfig['x_pos'], $journalLogoConfig['y_pos'], $journalLogoConfig['width']);
            } elseif ($imgtype === 'svg') {
                $pdfTemplate->ImageSVG($logoPath, $journalLogoConfig['x_pos'], $journalLogoConfig['y_pos'], $journalLogoConfig['width']);
            } else {
                $pdfTemplate->Image($logoPath, $journalLogoConfig['x_pos'], $journalLogoConfig['y_pos'], $journalLogoConfig['width']);
            }
            return true;
        }

        return false;
    }

}