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
    'footnotes' => 'processFootnotes',
  ];

  private const USE_MAP = [
    'footer' => 'genericUses',
    'header' => 'genericUses'
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
    $test = "";
    $fileUsesTest = [];

    $this->assignMetadata($metadata, $config);

    foreach ($catalog['build']['item'] as $part) {
      $pdf->SetHTMLHeader(''); # Desactivo el Header
      $pdf->SetHTMLFooter(''); # Desactivo el Footer

      $currentFile = $catalog[$part]['file'];
      $currentFileData = [
        'filepath' => $templatesDir . 'SUMARC/' . $templateName . '/' . $currentFile,
        'type' => $part
      ];
      $fileUses = [];
      if (!file_exists($currentFileData['filepath'])) {
        $error .= "$currentFile no encontrado \n";
      }

      $uses = (array) (isset($catalog[$part]['uses']['use']) ? $catalog[$part]['uses']['use'] : []);

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
            $fileUsesTest[] = $usesFileData;
          }
        }
      }
      
      foreach($fileUses as $use) {
        $test .= "Usando " . print_r($use, true) . " para ". $currentFileData['type'] . "\n";
        if(array_key_exists($use['type'], $this::USE_MAP)) {
          $fn = $this::USE_MAP[$use['type']];
          $this->$fn($use['filepath'], $pdf, $use['type']);
        }
        else {
          $this->defaultUses($use['filepath'], $pdf);
        }
      }
      
      if (array_key_exists($currentFileData['type'], $this::PROCESS_MAP)) {
        $fn = $this::PROCESS_MAP[$currentFileData['type']];
        $this->$fn($xpath, $dom, $htmlString, $pdf, $config, $citeProc, $currentFileData['filepath']);
      }
      else {
        $this->defaultProcessing($pdf, $currentFileData['filepath']);
      }
    }

    file_put_contents(__DIR__ . "/errors.txt", $error); # Ahora marco los errores de archivos faltantes en un txt. A futuro será un mensaje en OJS
    return $pdf;
  }

  private function genericUses($filepath, $pdf, $type) {
    $html = $this->templateManager->fetch($filepath);
    switch($type) {
      case "header":
        $pdf->SetHTMLHeader($html);
        break;
      case "footer":
        $pdf->SetHTMLFooter($html);
        break;
    }
  }

  private function defaultUses($filepath, $pdf) {
    $html = $this->templateManager->fetch($filepath);
    $pdf->WriteHTML($html); # Uses genérico, no tiene nada de especial, pero así no da error nunca y solo es un PDF "inesperado"
  }

  private function assignMetadata($metadata, $config) {
    foreach ($metadata as $key => $value) {
        $this->templateManager->assign($key, $value);
    }

    $licenses = $config->getLicenseConfig();
    $licenseName = array_search($metadata['license_url'], $licenses['links']);
    $licenseImg = $licenses['logos'][$licenseName];
    $this->templateManager->assign('license_logo', $licenseImg);

    $this->templateManager->assign('authors', $metadata['authors']);
    $this->templateManager->assign('orcid_logo', $config->getOrcidLogo());
  }

  private function defaultProcessing($pdf, $filepath) { # Este método sirve para renderizar cualquier cosa genérica que use metadatos  
    $html = $this->templateManager->fetch($filepath);
    $pdf->WriteHTML($html);
  }

  private function processReferences($xpath, $dom, $htmlString, $pdf, $config, $citeProc, $path)
  {
    $referencesAPA = $citeProc->getRawReferences();

    $referencesNodes = $xpath->evaluate('//div[contains(@class,"references-section")]//li');
    $references = $this->processingService->setReferencesAnchors($referencesAPA, $referencesNodes); # Agrego los tags <a> vacíos para las redirecciones de referencias

    $htmlString = $dom->saveHTML();

    foreach ($xpath->query('//a[contains(@class, "bibr")]') as $a) { # Agrego los tags <a> a las citas
      $this->processingService->processCitations($a, $dom, "citation_");
    }

    $htmlString = $dom->saveHTML();

    $referencesSection = $xpath->query('//div[contains(@class,"references-section")]');
    $referencesSection = $referencesSection->item(0);

    if ($referencesSection) {
      while ($referencesSection->hasChildNodes()) {
        $referencesSection->removeChild($referencesSection->firstChild);
      }
    }

    $htmlString = $dom->saveHTML();

    $this->processingService->processReferences($referencesSection, $references, $dom);

    $htmlString = $dom->saveHTML();

    $styles = $this->templateManager->fetch($path);
    $pdf->WriteHTML($styles);

    $this->writeReferences($htmlString, $pdf, 'references-section', $path, 'references'); # Escribo las references
  }

  public function processFootnotes($xpath, $dom, $htmlString, $pdf, $config, $citeProc, $path) {
    $footnotesNodes = $xpath->evaluate('//div[contains(@class,"footnotes-container")]//div');
    $footnotes = $this->processingService->setFootnotesAnchors($footnotesNodes); # Same con footnotes

    $htmlString = $dom->saveHTML();

    foreach ($xpath->query('//div[contains(@class, "footnote-item")]') as $a) { # Agrego los tags <a> a las citas footnote
      $this->processingService->processCitations($a, $dom, "footnote_");
    }

    $htmlString = $dom->saveHTML();

    $footnotesSection = $xpath->query('//div[contains(@class, "footnotes-container")]');
    $footnotesSection = $footnotesSection->item(0);

    if ($footnotesSection) {
      while ($footnotesSection->hasChildNodes()) {
        $footnotesSection->removeChild($footnotesSection->firstChild);
      }
    }

    $htmlString = $dom->saveHTML();

    $this->processingService->processReferences($footnotesSection, $footnotes, $dom);

    $htmlString = $dom->saveHTML();

    $styles = $this->templateManager->fetch($path);
    $pdf->WriteHTML($styles);

    $this->writeReferences($htmlString, $pdf, 'footnotes-container', $path, 'footnotes'); # Escibo las footnotes
  }

  private function writeReferences($htmlString, $pdf, $busqueda, $path)
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

  private function processBody($xpath, $dom, $htmlString, $pdf, $config, $citeProc, $path)
  {
    $styles = $this->templateManager->fetch($path);
    $pdf->WriteHTML($styles);

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

    $this->setTablesClass($bodyXpath, 'table'); # A todas les asigno la clase "table" para no pisar el CSS del footer/header
    $this->setTablesClass($bodyXpath, 'td');  
    $this->setTablesClass($bodyXpath, 'tr');  
    $this->setTablesClass($bodyXpath, 'th');

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

  private function setTablesClass($bodyXpath, $term) {
    $items = $bodyXpath->query('//' . $term);

    foreach($items as $item) {
      $item->setAttribute('class', 'table');
    }

    return $items;
  }

  public static function test()
  {
    return "hello world, leito was here";
  }
}
