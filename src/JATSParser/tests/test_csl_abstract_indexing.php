<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
use Seboettg\CiteProc\StyleSheet;
use Seboettg\CiteProc\CiteProc;

// Use the Spanish style
$styleName = __DIR__ . '/../Back/CSL/apa-spanish-SUMARC.csl';
$style = StyleSheet::loadStyleSheet($styleName);
$citeProc = new CiteProc($style, 'en-US'); // Use en-US to match the example language if needed, or es-ES

// Example:
// Hare, L. R., & O’Neill, K. (2000). Effectiveness and efficiency in small academic peer groups: A case study 
// (Accession No. 200010185) [Abstract from Sociological Abstracts]. 
// Small Group Research, 31(1), 24–53. https://doi.org/10.1177/104649640003100102

$articleData = (object) [
    'id' => 'item1',
    'type' => 'article-journal',
    'title' => 'Effectiveness and efficiency in small academic peer groups: A case study',
    'author' => [
        (object) ['family' => 'Hare', 'given' => 'L. R.'],
        (object) ['family' => 'O’Neill', 'given' => 'K.']
    ],
    'issued' => (object) ['date-parts' => [[2000]]],
    'container-title' => 'Small Group Research',
    'volume' => '31',
    'issue' => '1',
    'page' => '24-53',
    'DOI' => '10.1177/104649640003100102',
    
    // Workaround: Append Accession No. to title
    'title' => 'Effectiveness and efficiency in small academic peer groups: A case study (Accession No. 200010185)',
    // Use medium for the bracketed abstract
    'medium' => 'Abstract from Sociological Abstracts'
];

echo "--- REFERENCE DATA ---\n";
print_r($articleData);
echo "\n";

echo "--- RENDERED OUTPUT ---\n";
$result = $citeProc->render([$articleData], "bibliography");
echo $result . "\n";
