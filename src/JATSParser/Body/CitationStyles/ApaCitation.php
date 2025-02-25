<?php

use JATSParser\CitationStyles\CitationStyleManager;

require_once __DIR__ . '/CitationStyleManager.php';

class ApaCitation extends CitationStyleManager{
    
    //This method modifies the xref nodes and adds the authors and years in APA format, for example: (Author1 et al., year1; Author2, year2)...
    public static function CitationManager($xpath) {
        foreach ($xpath->evaluate("/article/body//sec//p//xref") as $xref) {
            $rid = $xref->getAttribute("rid");
            if ($rid) {
                $rids = explode(" ", $rid);
                $citations = [];
                foreach ($rids as $singleRid) {
                    $refElement = $xpath->query("/article/back//ref-list//ref[@id='$singleRid']")->item(0);
                    if ($refElement) {
                        $authorsArray = [];
                        $authorNodes = $xpath->query(".//element-citation//person-group[@person-group-type='author']//name", $refElement);
    
                        foreach ($authorNodes as $authorNode) {
                            $surnameNode = $authorNode->getElementsByTagName("surname")->item(0);
                            if ($surnameNode) {
                                $surname = $surnameNode->nodeValue;
                                if ($surname) {
                                    $authorsArray[] = $surname;
                                }
                            }
                        }
                        $authors = (count($authorsArray) >= 3) 
                            ? $authorsArray[0] . " et al." 
                            : implode(" y ", $authorsArray);

                        $yearNode = $xpath->query(".//element-citation//year", $refElement)->item(0);
                        $year = $yearNode ? $yearNode->nodeValue : "s.f.";
    
                        $citations[] = "$authors, $year";
                    }
                }
                $xref->nodeValue = "(" . implode("; ", $citations) . ")";
            }
        }
    }    
}