<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use JATSParser\Body\Document;
use JATSParser\HTML\Document as HTMLDocument;
use JATSParser\HTML\Reference;

// Load the chapter fixture
$xmlFile = __DIR__ . '/fixtures/temp_chapter_full.xml';
$doc = new Document($xmlFile);
$references = $doc->getReferences();

echo "Extracted References Count: " . count($references) . "\n";

if (empty($references)) {
    die("No references found.");
}

$chapter = $references[0];

echo "\n--- JATSParser Object Data ---\n";
// Use reflection or specific getters to inspect the object
echo "Class: " . get_class($chapter) . "\n";
echo "Title: " . $chapter->getTitle() . "\n";
echo "Book: " . $chapter->getBook() . "\n";
echo "Year: " . $chapter->getYear() . "\n";
echo "Volume: " . (method_exists($chapter, 'getVolume') ? $chapter->getVolume() : 'N/A') . "\n";
echo "Edition: " . (method_exists($chapter, 'getEdition') ? $chapter->getEdition() : 'N/A') . "\n";
echo "Pages: " . (method_exists($chapter, 'getPages') ? $chapter->getPages() : 'N/A') . "\n";
echo "Pub IDs: "; print_r($chapter->getPubIdType());

echo "\n--- CSL JSON Content ---\n";
// Convert to CSL Reference to see the final mapped data
$cslRef = new Reference($chapter);
print_r($cslRef->getContent());

// Render with CSL
$htmlDoc = new HTMLDocument($doc);
$htmlDoc->setReferences('apa', 'es');
$rendered = $htmlDoc->saveHTML();

echo "\n--- Rendered HTML ---\n";
echo strip_tags($rendered);
