<?php

namespace JATSParser\TemplateHandler\PDF;

use DOMDocument;

class PDFCreationService
{

  private $publicTemplateManager;
  private $privateTemplateManager;
  
  private $templateManager;

  private const PROCESS_MAP = [
    'body' => 'processBody',
    'references' => 'processReferences',
    'footnotes' => 'processFootnotes',
  ];

  private const USE_MAP = [
    'footer' => 'genericUses',
    'header' => 'genericUses'
  ];

  public function __construct($publicTemplateManager, $privateTemplateManager)
  {
    $this->publicTemplateManager = $publicTemplateManager;
    $this->privateTemplateManager = $privateTemplateManager;

    $this->templateManager = $publicTemplateManager;
  }

  private function whereToLook() {
    return $this->publicTemplateManager->getTemplateDir()[0]; # Temporal hasta que determine cómo hacer esto
  }

  # Sacar $templatesDir, crear un método que se encargue de retornar este valor en base a dónde se debe buscar la template, si la template es UNLP > concatenar CSS
  public function buildPDF($pdf, $htmlString, $xpath, $dom, $citeProc, $config, $metadata, $selectedTemplate)
  {
    $templatesDir = $this->whereToLook($selectedTemplate);

    $content = trim(file_get_contents("$templatesDir/$selectedTemplate/catalog.xml"));
    $xml = simplexml_load_string($content);
    $catalog = json_decode(json_encode($xml), true);
    $templateName = $catalog['template_name'];
    $error = "";

    $this->assignMetadata($metadata, $config);

    foreach ($catalog['media']['item'] as $mediaItem) {
      if (is_array($mediaItem) && isset($mediaItem['name']) && isset($mediaItem['file'])) {
        $optionalName = $mediaItem['name'];
        $optionalFile = $mediaItem['file'];
        $currentFileData = [
          'dataName' => $optionalName,
          'filepath' => "$templatesDir/$templateName/$optionalFile",
        ];
        if (!file_exists($currentFileData['filepath'])) {
          $error .= "Archivo opcional $optionalName no encontrado \n";
        } else {
          $this->templateManager->assign($currentFileData['dataName'], $currentFileData['filepath']);
        }
      }
    }

    foreach ($catalog['build']['item'] as $part) {
      $currentFile = $catalog[$part]['file'];
      $currentFileData = [
        'filepath' => "$templatesDir/$templateName/$currentFile",
        'type' => $part
      ];
      $fileUses = [];
      if (!file_exists($currentFileData['filepath'])) {
        $error .= "$currentFile no encontrado \n";
      }

      # $margins = $this->setCustomMargins($currentFileData['filepath']);
      # $pdf->WriteHTML($margins); # Esta función permite la utilización de márgenes propios en cada parte del PDF, pero se rompe header y footer. Dejo comentado el uso pero no elimino la lógica por si a futuro la librería lo arregla

      $pdf->SetHTMLHeader(''); # Desactivo el Header
      $pdf->SetHTMLFooter(''); # Desactivo el Footer

      $uses = (array) (isset($catalog[$part]['uses']['use']) ? $catalog[$part]['uses']['use'] : []);

      foreach ($uses as $use) {
        if (is_string($use) && isset($catalog[$use]) && isset($catalog[$use]['file'])) { # Más chequeos por la conversión de XML...
          $usesFile = $catalog[$use]['file'];
          $usesFileData = [
            'filepath' => "$templatesDir/$templateName/$usesFile",
            'type' => $use,
            'role' => $catalog[$use]['role'],
          ];
          if (!file_exists("$templatesDir/$templateName/$usesFile")) {
            $error .= "Archivo $usesFile no encontrado \n";
          } else {
            $fileUses[] = $usesFileData;
          }
        }
        else {
          $error .= "Artefacto $use no encontrado \n";
        }
      }

      foreach ($fileUses as $use) {
        if (array_key_exists($use['role'], $this::USE_MAP)) {
          $fn = $this::USE_MAP[$use['role']];
          $this->$fn($use['filepath'], $pdf, $use['role']);
        } else {
          $this->defaultProcessing($use['filepath'], $pdf); # Renderiza y escribe, por eso reuso el procesamiento y no es un método aparte
        }
      }

      if (array_key_exists($currentFileData['type'], $this::PROCESS_MAP)) {
        $fn = $this::PROCESS_MAP[$currentFileData['type']];
        $this->$fn($xpath, $dom, $htmlString, $pdf, $config, $citeProc, $currentFileData['filepath']);
      } else {
        $this->defaultProcessing($pdf, $currentFileData['filepath']);
      }
    }

    file_put_contents(__DIR__ . "/errors.txt", $error); # Ahora marco los errores de archivos faltantes en un txt. A futuro será un mensaje en OJS
    return $pdf->output('a', 'S');
  }

  private function genericUses($filepath, $pdf, $type)
  {
    $html = $this->templateManager->fetch($filepath);
    $html = str_replace('<pagenumber />', '{PAGENO}', $html); # Esto debe hacerse una vez renderizado, sino Smarty tira una Exception
    $html = str_replace('<totalpages />', '{nb}', $html);
    switch ($type) {
      case "header":
        $pdf->SetHTMLHeader($html, 'O');
        break;
      case "footer":
        $pdf->SetHTMLFooter($html, 'O');
        break;
    }
  }

  private function assignMetadata($metadata, $config)
  {
    foreach ($metadata as $key => $value) {
      if ($key === 'abstract_texts') {
        $value = str_replace('<br />', ' ', $value);
        $value = str_replace('<p>', '', $value);
        $value = str_replace('</p>', '', $value);
      }

      $this->templateManager->assign($key, $value);
    }

    $licenses = $config->getLicenseConfig();
    $licenseName = array_search($metadata['license_url'], $licenses['links']);
    $licenseImg = $licenses['logos'][$licenseName];

    $this->templateManager->assign('license_logo', $licenseImg);
    $this->templateManager->assign('authors', $metadata['authors']);
    $this->templateManager->assign('orcid_logo', $config->getOrcidLogo());
    $this->templateManager->assign('images', $config->getImages());
  }

  private function defaultProcessing($pdf, $filepath)
  { # Este método sirve para renderizar cualquier cosa genérica que use metadatos  
    $html = $this->templateManager->fetch($filepath);
    $pdf->WriteHTML($html);
  }

  private function processReferences($xpath, $dom, $htmlString, $pdf, $config, $citeProc, $path)
  {
    $referencesAPA = $citeProc->getRawReferences();

    $referencesNodes = $xpath->evaluate('//div[contains(@class,"references-section")]//li');
    $references = PDFProcessingService::setReferencesAnchors($referencesAPA, $referencesNodes); # Agrego los tags <a> vacíos para las redirecciones de referencias

    $htmlString = $dom->saveHTML();

    foreach ($xpath->query('//a[contains(@class, "bibr")]') as $a) { # Agrego los tags <a> a las citas
      PDFProcessingService::processCitations($a, $dom, "citation_");
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

    PDFProcessingService::processReferences($referencesSection, $references, $dom);

    $htmlString = $dom->saveHTML();

    $styles = $this->templateManager->fetch($path);
    $pdf->WriteHTML($styles);

    $this->writeReferences($htmlString, $pdf, 'references-section', $path, 'references'); # Escribo las references
  }

  public function processFootnotes($xpath, $dom, $htmlString, $pdf, $config, $citeProc, $path)
  {
    $footnotesNodes = $xpath->evaluate('//div[contains(@class,"footnotes-container")]//div');
    $footnotes = PDFProcessingService::setFootnotesAnchors($footnotesNodes); # Same con footnotes

    $htmlString = $dom->saveHTML();

    foreach ($xpath->query('//div[contains(@class, "footnote-item")]') as $a) { # Agrego los tags <a> a las citas footnote
      PDFProcessingService::processCitations($a, $dom, "footnote_");
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

    PDFProcessingService::processFootnotes($footnotesSection, $footnotes, $dom);

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

    if ($refs) {
      $newDoc = new \DOMDocument();
      $refsNode = $newDoc->importNode($refs, true);
      $newDoc->appendChild($refsNode);

      $r = PDFProcessingService::processExternalLinks($newDoc);

      $pdf->WriteHTML($r);
    }
  }

  private function processBody($xpath, $dom, $htmlString, $pdf, $config, $citeProc, $path)
  {
    $styles = $this->templateManager->fetch($path);
    $pdf->WriteHTML($styles);

    $referencesNodes = $xpath->evaluate('//a[contains(@class, "bibr")]'); # Procesar todas las citas, incluso si son múltiples
    foreach ($referencesNodes as $node) {
      PDFProcessingService::citeToLink($node, $dom, $xpath, $config);
    }

    $footnotesNodes = $xpath->evaluate('//a[contains(@class, "fn")]'); # Same con footnotes
    foreach ($footnotesNodes as $node) {
      PDFProcessingService::footnoteToLink($node, $dom);
    }

    $htmlString = $dom->saveHTML();

    $bodyDom = new \DOMDocument('1.0', 'utf-8');
    $bodyDom->loadHTML($htmlString);
    $bodyXpath = new \DOMXPath($bodyDom);

    $referencesSection = $bodyXpath->query('//div[contains(@class,"references-section")]');
    $referencesSection = $referencesSection->item(0);
    $footnotesSection = $bodyXpath->query('//div[contains(@class, "footnotes-container")]');
    $footnotesSection = $footnotesSection->item(0);

    PDFProcessingService::setTablesClass($bodyXpath, 'table'); # A todas les asigno la clase "table" para no pisar el CSS del footer/header
    PDFProcessingService::setTablesClass($bodyXpath, 'td');
    PDFProcessingService::setTablesClass($bodyXpath, 'tr');
    PDFProcessingService::setTablesClass($bodyXpath, 'th');

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
