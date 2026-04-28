<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
use Seboettg\CiteProc\StyleSheet;
use Seboettg\CiteProc\CiteProc;

// Use the Spanish style
$styleName = __DIR__ . '/../Back/CSL/apa-spanish-SUMARC.csl';
$style = StyleSheet::loadStyleSheet($styleName);
$citeProc = new CiteProc($style, 'en-US'); 

// Example:
// Ganster, D. C., Schaubroeck, J., Sime, W. E., & Mayes, B. T. (1991). The nomological validity of the Type A personality among employed adults [Monograph]. 
// Journal of Applied Psychology, 76(1), 143–168. https://doi.org/10.1037/0021-9010.76.1.143

$articleData = (object) [
    'id' => 'item1',
    'type' => 'article-journal',
    'title' => 'The nomological validity of the Type A personality among employed adults',
    'author' => [
        (object) ['family' => 'Ganster', 'given' => 'D. C.'],
        (object) ['family' => 'Schaubroeck', 'given' => 'J.'],
        (object) ['family' => 'Sime', 'given' => 'W. E.'],
        (object) ['family' => 'Mayes', 'given' => 'B. T.']
    ],
    'issued' => (object) ['date-parts' => [[1991]]],
    'container-title' => 'Journal of Applied Psychology',
    'volume' => '76',
    'issue' => '1',
    'page' => '143-168',
    'DOI' => '10.1037/0021-9010.76.1.143',
    
    // Testing medium for Monograph
    'medium' => 'Monograph'
];

echo "--- REFERENCE DATA ---\n";
print_r($articleData);
echo "\n";

echo "--- RENDERED OUTPUT ---\n";
$result = $citeProc->render([$articleData], "bibliography");
echo $result . "\n";
