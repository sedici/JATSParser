<?php
// tests/test_thesis_mapping.php

require_once __DIR__ . '/../../../vendor/autoload.php';

use JATSParser\Body\Document as JATSDocument;
use JATSParser\HTML\Document as HTMLDocument;

$xmlPath = __DIR__ . '/fixtures/thesis_ref.xml';

if (!file_exists($xmlPath)) {
    die("Error: XML file not found at $xmlPath\n");
}

try {
    // 1. Parse JATS XML
    $jatsDoc = new JATSDocument($xmlPath);
    
    // 2. Create HTML Document which handles CSL conversion
    $htmlDoc = new HTMLDocument($jatsDoc);
    
    // 3. Set reference style (using es to ensure we load a valid CSL from correct path)
    $htmlDoc->setReferences('apa', 'es');

    // 4. Inspect the protected property citeProcReferences using reflection
    $reflection = new ReflectionClass($htmlDoc);
    $property = $reflection->getProperty('citeProcReferences');
    $property->setAccessible(true);
    $references = $property->getValue($htmlDoc);

    echo json_encode($references, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    echo "\n\n--- Rendered Output ---\n";
    // We can just print the whole HTML doc or find the reference list
    echo $htmlDoc->saveHTML();
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
