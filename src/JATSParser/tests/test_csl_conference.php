<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
use Seboettg\CiteProc\StyleSheet;
use Seboettg\CiteProc\CiteProc;

// Use the Spanish style
$styleName = __DIR__ . '/../Back/CSL/apa-spanish-SUMARC.csl';
$style = StyleSheet::loadStyleSheet($styleName);
$citeProc = new CiteProc($style, 'en-US'); 

// User's example data converted to CSL-JSON
// <conf-name>Conference name</conf-name> -> mapped to 'event' in Reference.php
// <conf-loc>Conference Location</conf-loc> -> mapped to 'event-place' in Reference.php
// Reference.php sets type to 'conference' (which might need to be 'paper-conference')

$articleData = (object) [
    'id' => 'item1',
    'type' => 'paper-conference', // Standard CSL type for conference paper. Reference.php uses 'conference', we should test both.
    'title' => 'Gun violence: An event on the power of community',
    'author' => [
        (object) ['family' => 'Evans', 'given' => 'Alexander Carl'],
        (object) ['family' => 'Jr Garbarino', 'given' => 'Johnson'],
        (object) ['family' => 'Bocanegra', 'given' => 'Esteban'],
        (object) ['family' => 'Kinscheriff', 'given' => 'Ronald Tyron'],
        (object) ['family' => 'Márquez-Greene', 'given' => 'Norberto']
    ],
    'issued' => (object) ['date-parts' => [[2019, 8, 8]]],
    'page' => '10-29',
    
    // Mapped fields in Reference.php
    'event' => 'Conference name',
    'event-place' => 'Conference Location',
    // 'source' in JATS often maps to container-title (the proceedings title)
    'container-title' => 'Source', 
];

echo "--- REFERENCE DATA (type: paper-conference) ---\n";
print_r($articleData);
echo "\n";
echo "--- RENDERED OUTPUT ---\n";
$result = $citeProc->render([$articleData], "bibliography");
echo $result . "\n\n";

// Test with type 'conference' as seen in Reference.php
$articleData2 = clone $articleData;
$articleData2->type = 'conference';

echo "--- REFERENCE DATA (type: conference) ---\n";
print_r($articleData2);
echo "\n";
echo "--- RENDERED OUTPUT ---\n";
$result2 = $citeProc->render([$articleData2], "bibliography");
echo $result2 . "\n";
