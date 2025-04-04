<?php namespace JATSParser\PDF\Templates\Renderers;

use JATSParser\PDF\PDFConfig\Configuration;

use JATSParser\PDF\Templates\Renderers\SingleRenderer\JournalLogo;

/**
 * Renderer class for handling PDF rendering tasks.
 *
 * @package JATSParser\PDF\Templates\Renderers
 */

class Renderer {

    public static function renderJournalLogo($pdfTemplate, Array $journalLogoConfig): void {
        JournalLogo::renderJournalLogo($journalLogoConfig, $pdfTemplate);
    } 

}