<?php namespace JATSParser\CitationStyles;

require_once __DIR__ . '/ApaCitation.php';
require_once __DIR__ . '/../../PDF/PDFConfig/Configuration.php';

use JATSParser\PDF\PDFConfig\Configuration;

abstract class CitationStyleManager {

    public static function styleManagerSelector($xpath) {
        $citationStyle = Configuration::getCitationStyle(); //get citation style from Configuration class (for example: APA, MLA, Vancouver, etc.) you can change it to your own citation style.
        $citationString = ucfirst(strtolower($citationStyle)) . 'Citation';         
        $citationString::CitationManager($xpath); //call the method to modify the xref nodes with a specific format, for example: Apa, Vancouver, etc.
    }
    
    abstract static function CitationManager($xpath);
}