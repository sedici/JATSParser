<?php

namespace JATSParser\TemplateHandler;

use APP\template\TemplateManager;
use DOMDocument;

class PDFCreationService
{

  private $templateManager;
  private $processingService;

  private const PROCESS_MAP = [
    'body' => 'processBody',
    'references' => 'processReferences'
  ];

  private const USE_MAP = [
    'footer' => 'useFooter',
    'header' => 'useHeader'
  ];

  public function __construct($templateManager, $processingService)
  {
    $this->templateManager = TemplateManager::getManager();
    $this->processingService = $processingService;
  }

  public function buildPDF($templatesDir, $pdf, $htmlString, $xpath, $dom, $citeProc, $config)
  {
    $content = trim(file_get_contents($templatesDir . 'SUMARC/REDIC/catalog.xml')); # Esto debería ser ruta de OJS, no estar hardcodeado
    $xml = simplexml_load_string($content);
    $catalog = json_decode(json_encode($xml), true);
    $templateName = $catalog['template_name'];
    $error = "";

    foreach ($catalog['build']['item'] as $part) {
      $currentFile = $catalog[$part]['file'];
      $currentFileData = [
        'filepath' => $templatesDir . 'SUMARC/' . $templateName . '/' . $currentFile,
        'type' => $part
      ];
      $fileUses = [];
      if (!file_exists($currentFileData['filepath'])) {
        $error .= "$currentFile no encontrado \n";
      }

      $uses = isset($catalog[$part]['uses']) ? $catalog[$part]['uses'] : [];
      if (!is_array($uses)) {
        $uses = $uses ? [$uses] : []; # Si no es un Array, lo hago array vacío, la conversión a XML
      }

      foreach ($uses as $use) {
        if (is_string($use) && isset($catalog[$use]) && isset($catalog[$use]['file'])) { # Más chequeos por la conversión de XML...
          $usesFile = $catalog[$use]['file'];
          $usesFileData = [
            'filepath' => $templatesDir . 'SUMARC/' . $templateName . '/' . $usesFile,
            'type' => $use
          ];
          if (!file_exists($templatesDir . 'SUMARC/' . $templateName . '/' . $usesFile)) {
            $error .= "$usesFile no encontrado \n";
          } else {
            $fileUses[] = $usesFileData;
          }
        }
      }

      if ($currentFileData['type'] === 'body') $this->builderHelper($pdf, $htmlString, $xpath, $dom, $citeProc, $config);
    }
    file_put_contents(__DIR__ . "/errors.txt", $error); # Ahora marco los errores de archivos faltantes en un txt. A futuro será un mensaje en OJS
    return $pdf;
  }

  private function builderHelper($pdf, $htmlString, $xpath, $dom, $citeProc, $config)
  {
    # $html = $this->templateManager->fetch('SUMARC/test.tpl');

    $htmlString = $this->processBody($xpath, $dom, $htmlString, $pdf, $config); # Debería procesar todo y escribir solo el body > crear una nueva variable SIN references-section ni footnotes-container
    $this->processReferences($citeProc, $dom, $xpath, $htmlString, $pdf); # Debería procesar todo y solo escribir las referencias > crear una nueva variable de solo references-section y footnotes-section

    #$pdf->WriteHTML($htmlString);
  }

  private function processReferences($citeProc, $dom, $xpath, $htmlString, $pdf)
  {
    $referencesAPA = $citeProc->getRawReferences();

    $referencesNodes = $xpath->evaluate('//div[contains(@class,"references-section")]//li');
    $references = $this->processingService->setReferencesAnchors($referencesAPA, $referencesNodes); # Agrego los tags <a> vacíos para las redirecciones de referencias

    $footnotesNodes = $xpath->evaluate('//div[contains(@class,"footnotes-container")]//div');
    $footnotes = $this->processingService->setFootnotesAnchors($footnotesNodes); # Same con footnotes

    $htmlString = $dom->saveHTML();

    foreach ($xpath->query('//a[contains(@class, "bibr")]') as $a) { # Agrego los tags <a> a las citas
      $this->processingService->processCitations($a, $dom, "citation_");
    }

    foreach ($xpath->query('//div[contains(@class, "footnote-item")]') as $a) { # Agrego los tags <a> a las citas footnote
      $this->processingService->processCitations($a, $dom, "footnote_");
    }

    $htmlString = $dom->saveHTML();

    $referencesSection = $xpath->query('//div[contains(@class,"references-section")]');
    $referencesSection = $referencesSection->item(0);
    $footnotesSection = $xpath->query('//div[contains(@class, "footnotes-container")]');
    $footnotesSection = $footnotesSection->item(0);

    if ($referencesSection) {
      while ($referencesSection->hasChildNodes()) {
        $referencesSection->removeChild($referencesSection->firstChild);
      }
    }

    if ($footnotesSection) {
      while ($footnotesSection->hasChildNodes()) {
        $footnotesSection->removeChild($footnotesSection->firstChild);
      }
    }

    $htmlString = $dom->saveHTML();

    $this->processingService->processReferences($referencesSection, $references, $dom);
    $this->processingService->processReferences($footnotesSection, $footnotes, $dom); # Ver esto, no genera una nueva lista sino que agarra la misma

    $htmlString = $dom->saveHTML();

    $this->writeReferences($htmlString, $pdf, 'references-section', $config); # Escribo las references
    $this->writeReferences($htmlString, $pdf, 'footnotes-container', $config); # Escibo las footnotes

    file_put_contents(__DIR__ . '/test.html', $htmlString);
  }

  private function writeReferences($htmlString, $pdf, $busqueda, $config) # Sacar $config de toda la cascada
  {
    $referencesDom = new \DOMDocument('1.0', 'utf-8');
    $referencesDom->loadHTML($htmlString);
    $referencesDom->saveHTML();
    $referencesXpath = new \DOMXPath($referencesDom);

    $refsToWrite = $referencesXpath->query("//div[contains(@class, '$busqueda')]");
    $refs = $refsToWrite->item(0);

    $newDoc = new \DOMDocument();
    $refsNode = $newDoc->importNode($refs, true);
    $newDoc->appendChild($refsNode);
    $r = $newDoc->saveHTML();

    $pdf->WriteHTML($r);
  }

  private function processBody($xpath, $dom, $htmlString, $pdf, $config)
  {
    $referencesNodes = $xpath->evaluate('//a[contains(@class, "bibr")]'); # Procesar todas las citas, incluso si son múltiples
    foreach ($referencesNodes as $node) {
      $this->processingService->citeToLink($node, $dom, $xpath, $config);
    }

    $footnotesNodes = $xpath->evaluate('//a[contains(@class, "fn")]'); # Same con footnotes
    foreach ($footnotesNodes as $node) {
      $this->processingService->footnoteToLink($node, $dom);
    }

    $htmlString = $dom->saveHTML();

    $bodyDom = new \DOMDocument('1.0', 'utf-8');
    $bodyDom->loadHTML($htmlString);
    $bodyXpath = new \DOMXPath($bodyDom);

    $referencesSection = $bodyXpath->query('//div[contains(@class,"references-section")]');
    $referencesSection = $referencesSection->item(0);
    $footnotesSection = $bodyXpath->query('//div[contains(@class, "footnotes-container")]');
    $footnotesSection = $footnotesSection->item(0);

    if ($referencesSection) {
      while ($referencesSection->hasChildNodes()) {
        $referencesSection->removeChild($referencesSection->firstChild);
      }
    }

    if ($footnotesSection) {
      while ($footnotesSection->hasChildNodes()) {
        $footnotesSection->removeChild($footnotesSection->firstChild);
      }
    }

    $isolatedBody = $bodyDom->saveHTML();
    $pdf->writeHTML($isolatedBody);
    return $htmlString;
  }

  public static function test()
  {
    return "hello world, leito was here";
  }
}
