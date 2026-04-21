<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
use Seboettg\CiteProc\StyleSheet;
use Seboettg\CiteProc\CiteProc;

// Use the Spanish style as it is likely the relevant one
$styleName = __DIR__ . '/../Back/CSL/apa-spanish-SUMARC.csl';
$style = StyleSheet::loadStyleSheet($styleName);
$citeProc = new CiteProc($style, 'es-ES');

// Base data for the retracted article
$articleData = (object) [
    'id' => 'item1',
    'type' => 'article-journal',
    'title' => 'Silence and table manners: When environments activate norms',
    'author' => [
        (object) ['family' => 'Joly', 'given' => 'J. F.'],
        (object) ['family' => 'Stapel', 'given' => 'D. A.'],
        (object) ['family' => 'Lindenberg', 'given' => 'S. M.']
    ],
    'issued' => (object) ['date-parts' => [[2008]]],
    'container-title' => 'Personality and Social Psychology Bulletin',
    'volume' => '34',
    'issue' => '8',
    'page' => '1047-1056',
    'DOI' => '10.1177/0146167208318401'
];

echo "--- REFERENCE DATA ---\n";
print_r($articleData);
echo "\n";

// Test Case 1: Using 'note' field
echo "--- TEST CASE 1: 'note' field ---\n";
$data1 = clone $articleData;
$data1->note = 'Retraction published 2012, Personality and Social Psychology Bulletin, 38[10], 1378';
$result1 = $citeProc->render([$data1], "bibliography");
echo $result1 . "\n";

// Test Case 2: Using 'status' field (unlikely to work directly without CSL support, but worth checking)
echo "--- TEST CASE 2: 'status' field ---\n";
$data2 = clone $articleData;
$data2->status = 'Retraction published 2012, Personality and Social Psychology Bulletin, 38[10], 1378';
$result2 = $citeProc->render([$data2], "bibliography");
echo $result2 . "\n";

// Test Case 3: Appending to title (Manual workaround check)
echo "--- TEST CASE 3: Appended to title ---\n";
$data3 = clone $articleData;
$data3->title .= ' (Retraction published 2012, Personality and Social Psychology Bulletin, 38[10], 1378)';
$result3 = $citeProc->render([$data3], "bibliography");
echo $result3 . "\n";
