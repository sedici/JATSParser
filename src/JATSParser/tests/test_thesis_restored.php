<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use JATSParser\Body\Document as JATSDocument;
use JATSParser\HTML\Document;

// Integration test for restored Thesis logic and CSL
$xmlFile = __DIR__ . '/fixtures/temp_restored.xml';

// 1. Parse using Body\Document (which uses Thesis.php logic)
$jatsDoc = new JATSDocument($xmlFile);
$references = $jatsDoc->getReferences();

if (empty($references)) {
    die("Error: No references extracted from fixture.\n");
}

$thesis = $references[0]; // Logic verification
echo "Mapping Verification:\n";
echo "Publisher Name (Archive): " . ($thesis->getPubIdType()['archive'] ?? 'Not Set') . "\n";
echo "Publisher (Institution): " . $thesis->getPublisherName() . "\n";

// 2. Instantiate HTML\Document
$htmlDoc = new Document($jatsDoc);

// 3. Set references with 'apa' style and 'es' language to trigger SUMARC CSL
// This will test verify apa-spanish-SUMARC.csl logic
$htmlDoc->setReferences('apa', 'es');

// 4. Output HTML
echo "\n--- Rendered HTML ---\n";
echo $htmlDoc->saveHTML();

