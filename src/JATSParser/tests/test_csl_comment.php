<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
use Seboettg\CiteProc\StyleSheet;
use Seboettg\CiteProc\CiteProc;

// Use the Spanish style
$styleName = __DIR__ . '/../Back/CSL/apa-spanish-SUMARC.csl';
$style = StyleSheet::loadStyleSheet($styleName);
$citeProc = new CiteProc($style, 'en-US'); 

// Example:
// sidneyf. (2020, October 7). Oh, I don’t know; perhaps the common-sense conclusion that packing people together — for hours — like sardines — may be an 
// [Comment on the article “When will it be safe to travel again?”]. 
// The Washington Post. https://wapo.st/3757UlS

$articleData = (object) [
    'id' => 'item1',
    'type' => 'article-newspaper',
    'author' => [
        (object) ['given' => 'sidneyf.'] // Assuming single name or username
    ],
    'issued' => (object) ['date-parts' => [[2020, 10, 7]]],
    'title' => 'Oh, I don’t know; perhaps the common-sense conclusion that packing people together — for hours — like sardines — may be an',
    'container-title' => 'The Washington Post',
    'URL' => 'https://wapo.st/3757UlS',
    
    // Testing medium for Comment
    'medium' => 'Comment on the article “When will it be safe to travel again?”'
];

echo "--- REFERENCE DATA ---\n";
print_r($articleData);
echo "\n";

echo "--- RENDERED OUTPUT ---\n";
$result = $citeProc->render([$articleData], "bibliography");
echo $result . "\n";
