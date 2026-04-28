<?php

require_once '/home/tomas/Escritorio/Trabajo-proyecto-xml/CONTENEDORES-OJS/ojs-docker-3.4/data/public_ojs/plugins/generic/jatsParser/JATSParser/vendor/autoload.php';

use JATSParser\Back\Journal;
use JATSParser\Back\Book;
use JATSParser\Back\Chapter;
use JATSParser\Back\Conference;
use JATSParser\Back\Dataset;
use JATSParser\Back\Article;
use JATSParser\Back\Newspaper;
use JATSParser\Back\Magazine;
use JATSParser\Back\Thesis;

$tests = [
    'Journal' => Journal::class,
    'Book' => Book::class,
    'Chapter' => Chapter::class,
    'Conference' => Conference::class,
    'Dataset' => Dataset::class,
    'Article' => Article::class,
    'Newspaper' => Newspaper::class,
    'Magazine' => Magazine::class,
    'Thesis' => Thesis::class,
];

$passed = 0;
$failed = 0;

foreach ($tests as $name => $class) {
    try {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><article><back><ref-list><ref id=\"test-1\"><mixed-citation publication-type=\"".strtolower($name)."\"><uri>www.google.com</uri></mixed-citation></ref></ref-list></back></article>";
        $tmpFile = tempnam(sys_get_temp_dir(), 'jats_test_');
        file_put_contents($tmpFile, $xml);
        
        $doc = new \JATSParser\Body\Document($tmpFile);
        $refs = $doc->getReferences();
        if (count($refs) == 0) {
            echo "FAIL: {$name} resulted in 0 references parsed.\n";
            $failed++;
            continue;
        }
        $ref = $refs[0];
        $url = $ref->getUrl();

        if ($url === 'www.google.com') {
            echo "PASS: {$name} extracted URL correctly: {$url}\n";
            $passed++;
        } else {
            echo "FAIL: {$name} failed to extract URL. Got: '{$url}' (Parsed as " . get_class($ref) . ")\n";
            $failed++;
        }
        unlink($tmpFile);
    } catch (\Exception $e) {
         echo "ERROR: {$name} threw exception: " . $e->getMessage() . "\n";
         $failed++;
    }
}

echo "\nTests Complete: {$passed} Passed, {$failed} Failed\n";
