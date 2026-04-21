<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
use Seboettg\CiteProc\StyleSheet;
use Seboettg\CiteProc\CiteProc;

// 1. Load the original CSL content
$originalStylePath = __DIR__ . '/../Back/CSL/apa-spanish-SUMARC.csl';
$cslContent = file_get_contents($originalStylePath);

// 2. Modify the CSL content to add <text macro="database-location"/> to the container-title block
// The block to modify is:
// <else-if variable="container-title">
//   <group delimiter="; ">
//     <text macro="secondary-contributors"/>
//     <choose>
//       <if type="broadcast graphic map motion_picture song" match="any">
//         <text macro="number"/>
//       </if>
//     </choose>
//   </group>
// </else-if>

// We will inject <text macro="database-location"/> into that group.
$searchBlock = '<else-if variable="container-title">
          <group delimiter="; ">
            <text macro="secondary-contributors"/>';

$replaceBlock = '<else-if variable="container-title">
          <group delimiter="; ">
            <text macro="secondary-contributors"/>
            <text macro="database-location"/>'; // Added this line

$modifiedCslContent = str_replace($searchBlock, $replaceBlock, $cslContent);

// 3. Save modified CSL to a temporary file
$tempCslPath = tempnam(sys_get_temp_dir(), 'csl_mod') . '.csl';
file_put_contents($tempCslPath, $modifiedCslContent);

// 4. Run CiteProc with the modified style
$style = StyleSheet::loadStyleSheet($tempCslPath);
$citeProc = new CiteProc($style, 'en-US');

// Example data with Archive Location (Accession No.) properly mapped
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
    
    // Now using the proper field
    'archive_location' => 'Accession No. 200010185',
    'medium' => 'Abstract from Sociological Abstracts'
];

echo "--- REFERENCE DATA ---\n";
print_r($articleData);
echo "\n";

echo "--- RENDERED OUTPUT (With Modified CSL) ---\n";
$result = $citeProc->render([$articleData], "bibliography");
echo $result . "\n";

// Clean up
unlink($tempCslPath);
