<?php namespace JATSParser\PDF\Templates\Renderers\SingleRenderer;

class JournalLogo {
    
    public static function renderJournalLogo(Array $journalLogoConfig, $pdfTemplate): void {
        
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