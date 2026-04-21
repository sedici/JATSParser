<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use JATSParser\Back\Document; // Note: In test_chapter_full_mapping it used JATSParser\Body\Document but Back/Document seems more appropriate for references? No, let's check imports.
// test_chapter_full_mapping used: use JATSParser\Body\Document;
// Let's stick to what worked there.
use JATSParser\Body\Document as BodyDocument;
use JATSParser\HTML\Reference;

// Load the fixture
$xmlFile = __DIR__ . '/fixtures/temp_conference.xml';

// We need to parse the document to get references.
// The Document class in Body seems to be the entry point for parsing full JATS.
$doc = new BodyDocument($xmlFile);
$references = $doc->getReferences();

echo "Extracted References Count: " . count($references) . "\n";

if (empty($references)) {
    die("No references found.");
}

$conference = $references[0];

echo "\n--- JATSParser Object Data ---\n";
echo "Class: " . get_class($conference) . "\n";
echo "Title (Expected 'Gun violence...'): " . $conference->getTitle() . "\n";
// Check if getSource exists
if (method_exists($conference, 'getSource')) {
    echo "Source (Expected 'Source'): " . $conference->getSource() . "\n";
} else {
    echo "Source method 'getSource' DOES NOT EXIST.\n";
}

echo "Conf Name: " . (method_exists($conference, 'getConfName') ? $conference->getConfName() : 'N/A') . "\n";
echo "Conf Loc: " . (method_exists($conference, 'getConfLoc') ? $conference->getConfLoc() : 'N/A') . "\n";

echo "\n--- CSL JSON Content (via Reference.php) ---\n";
$cslRef = new Reference($conference);
$content = $cslRef->getContent();
print_r($content);
