<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use JATSParser\Body\Document as JATSDocument;
use JATSParser\HTML\Document;

// Update test script to use integration logic
$xmlFile = __DIR__ . '/fixtures/temp_generic.xml';

// 1. Parse using Body\Document (which uses Thesis.php logic)
$jatsDoc = new JATSDocument($xmlFile);
$references = $jatsDoc->getReferences();

// 2. Instantiate HTML\Document
$htmlDoc = new Document($jatsDoc);



// 3. Set references with 'apa' style and 'en' language to trigger generic CSL
// Note: setReferences signature: (string $citationStyle, string $citationLang, array $references)
$htmlDoc->setReferences('apa', 'en');

// 4. Output HTML
echo $htmlDoc->saveHTML();
