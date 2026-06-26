<?php
// tests/test_user_confproc.php

require_once __DIR__ . '/../../../vendor/autoload.php';

use JATSParser\Body\Document as JATSDocument;
use JATSParser\HTML\Document as HTMLDocument;

$xmlPath = __DIR__ . '/fixtures/user_confproc.xml';

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

    echo "=== CSL-JSON REPRESENTATION GENERATED FOR CITEPROC ===\n";
    echo json_encode($references, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    echo "\n\n=== RENDERED HTML BIBLIOGRAPHY (APA style, 'es' locale) ===\n";
    echo $htmlDoc->saveHTML();

    // 5. Test another style: Vancouver
    $htmlDocVancouver = new HTMLDocument($jatsDoc);
    $htmlDocVancouver->setReferences('vancouver', 'es');

    echo "\n\n=== RENDERED HTML BIBLIOGRAPHY (Vancouver style) ===\n";
    echo $htmlDocVancouver->saveHTML();
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
