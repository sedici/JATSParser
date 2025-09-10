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
    'references' => 'processReferences',
  ];

  private const USE_MAP = [
    'footer' => 'useFooter',
    'header' => 'useHeader'
  ];

  public function __construct($templateManager, $processingService)
  {
    $this->templateManager = $templateManager;
    $this->processingService = $processingService;
  }

  public function buildPDF($templatesDir, $pdf, $htmlString, $xpath, $dom, $citeProc, $config, $metadata)
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
      
      if (array_key_exists($currentFileData['type'], $this::PROCESS_MAP)) {
        $fn = $this::PROCESS_MAP[$currentFileData['type']];
        $this->$fn($xpath, $dom, $htmlString, $pdf, $config, $citeProc);
      }
      else {
        $this->defaultProcessing($pdf, $metadata, $currentFileData['filepath'], $config);
      }
    }
    file_put_contents(__DIR__ . "/errors.txt", $error); # Ahora marco los errores de archivos faltantes en un txt. A futuro será un mensaje en OJS
    return $pdf;
  }

  private function defaultProcessing($pdf, $metadata, $filepath, $config) { # Este método escribe directamente la front page ya que se genera en base al TPL y los metadatos ne cesarios
    foreach ($metadata as $key => $value) {
        $this->templateManager->assign($key, $value);
    }

    $licenses = $config->getLicenseConfig();
    $licenseName = array_search($metadata['license_url'], $licenses['links']);
    $licenseImg = $licenses['logos'][$licenseName];
    $this->templateManager->assign('license_logo', $licenseImg);

    $this->templateManager->assign('authors', $metadata['authors']);
    $this->templateManager->assign('orcid_logo', $config->getOrcidLogo());
    
    $html = $this->templateManager->fetch($filepath);
    $pdf->WriteHTML($html);
  }

  private function processReferences($xpath, $dom, $htmlString, $pdf, $config, $citeProc)
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
    $this->processingService->processReferences($footnotesSection, $footnotes, $dom);

    $htmlString = $dom->saveHTML();

    $this->writeReferences($htmlString, $pdf, 'references-section'); # Escribo las references
    $this->writeReferences($htmlString, $pdf, 'footnotes-container'); # Escibo las footnotes
  }

  private function writeReferences($htmlString, $pdf, $busqueda)
  {
    $referencesDom = new \DOMDocument('1.0', 'utf-8');
    $referencesDom->loadHTML($htmlString);
    $referencesDom->saveHTML();
    $referencesXpath = new \DOMXPath($referencesDom);

    $refsToWrite = $referencesXpath->query("//div[contains(@class, '$busqueda')]");
    $refs = $refsToWrite->item(0);

    if($refs) {
      $newDoc = new \DOMDocument();
      $refsNode = $newDoc->importNode($refs, true);
      $newDoc->appendChild($refsNode);
      $r = $newDoc->saveHTML();

      $pdf->WriteHTML($r);
    }
  }

  private function processBody($xpath, $dom, $htmlString, $pdf, $config, $citeProc)
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
