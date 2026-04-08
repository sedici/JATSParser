<?php

namespace JATSParser\TemplateHandler\HTML;

use DOMDocument;

class HTMLCreationService
{

  private $publicTemplateManager;
  private $privateTemplateManager;
  
  private $templateManager;
  private $finalHtml = "";

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
  }

  private function whereToLook($selectedTemplate, $file) {

    $privateDir = $this->privateTemplateManager->getTemplateDir()[0];
    $publicDir = $this->publicTemplateManager->getTemplateDir()[0];

    if(file_exists("$privateDir/$selectedTemplate/$file")) {
      $this->templateManager = $this->privateTemplateManager;
      return $privateDir;
    }

    $this->templateManager = $this->publicTemplateManager;
    return $publicDir;
  }

  # Sacar $templatesDir, crear un método que se encargue de retornar este valor en base a dónde se debe buscar la template, si la template es UNLP > concatenar CSS
  public function buildHTML($htmlString, $xpath, $dom, $citeProc, $config, $metadata, $selectedTemplate, $ojsConfiguration)
  { 
    $templatesDir = $this->publicTemplateManager->getTemplateDir()[0]; # Primero busco el catálogo, el cual se habló que NO puede ser sobreescribible. Si se quiere modificar se deben armar una plantilla nueva de 0.
    $content = trim(file_get_contents("$templatesDir/$selectedTemplate/catalog.xml"));
    $xml = simplexml_load_string($content);
    $catalog = json_decode(json_encode($xml), true);
    $error = "";

    # Después verifico la existencia del arcivo opcional de CSS Extra el cual tiene la posibilidad de sobreescribir todo el CSS del PDF, por lo tanto lo hago acá ya que el catálogo NO es modificable
    $templatesDir = $this->privateTemplateManager->getTemplateDir()[0];
    $file = "$templatesDir/$selectedTemplate/extraCSS.css";
    if(file_exists($file)) {
      $this->publicTemplateManager->assign('extraCSS', $file);
      $this->privateTemplateManager->assign('extraCSS', $file);
    }

    $this->assignMetadata($metadata, $config, $ojsConfiguration);

    foreach ($catalog['media']['item'] as $mediaItem) {
      if (is_array($mediaItem) && isset($mediaItem['name']) && isset($mediaItem['file'])) {
        $templatesDir = $this->whereToLook($selectedTemplate, $mediaItem['file']);
        $optionalName = $mediaItem['name'];
        $optionalFile = $mediaItem['file'];
        $currentFileData = [
          'dataName' => $optionalName,
          'filepath' => "$templatesDir/$selectedTemplate/$optionalFile",
        ];
        if (!file_exists($currentFileData['filepath'])) {
          $error .= "Archivo opcional $optionalName no encontrado \n";
        } else {
          $this->publicTemplateManager->assign($currentFileData['dataName'], $currentFileData['filepath']);
          $this->privateTemplateManager->assign($currentFileData['dataName'], $currentFileData['filepath']);
        }
      }
    }

    foreach ($catalog['build']['item'] as $part) {
      $currentFile = $catalog[$part]['file'];
      $templatesDir = $this->whereToLook($selectedTemplate, $currentFile);
      $currentFileData = [
        'filepath' => "$templatesDir/$selectedTemplate/$currentFile",
        'type' => $part
      ];
      $fileUses = [];
      if (!file_exists($currentFileData['filepath'])) {
        $error .= "$currentFile no encontrado \n";
      }

      // $finalHtml->SetHTMLHeader(''); # Desactivo el Header ## Acá ver cómo acomodar para el uso de header y footer
      // $finalHtml->SetHTMLFooter(''); # Desactivo el Footer

      $uses = (array) (isset($catalog[$part]['uses']['use']) ? $catalog[$part]['uses']['use'] : []);

      foreach ($uses as $use) {
        if (is_string($use) && isset($catalog[$use]) && isset($catalog[$use]['file'])) { # Más chequeos por la conversión de XML...
          $usesFile = $catalog[$use]['file'];
          $templatesDir = $this->whereToLook($selectedTemplate, $usesFile);
          $usesFileData = [
            'filepath' => "$templatesDir/$selectedTemplate/$usesFile",
            'type' => $use,
            'role' => $catalog[$use]['role'],
          ];
          if (!file_exists($usesFileData['filepath'])) {
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
          $this->$fn($use['filepath'], $use['role']);
        } else {
          $this->defaultProcessing($use['filepath'], ); # Renderiza y escribe, por eso reuso el procesamiento y no es un método aparte
        }
      }

      if (array_key_exists($currentFileData['type'], $this::PROCESS_MAP)) {
        $fn = $this::PROCESS_MAP[$currentFileData['type']];
        $this->$fn($xpath, $dom, $htmlString, $config, $citeProc, $currentFileData['filepath']);
      } else {
        $this->defaultProcessing($currentFileData['filepath']);
      }
    }

    file_put_contents(__DIR__ . "/errors.txt", $error); # Ahora marco los errores de archivos faltantes en un txt. A futuro será un mensaje en OJS
    return $this->finalHtml;
  }

  private function genericUses($filepath, $type) # Este método no tiene demasiado sentido así como está, ver qué onda
  {
    $html = $this->templateManager->fetch($filepath);
    $html = str_replace('<pagenumber />', '{PAGENO}', $html); # Esto debe hacerse una vez renderizado, sino Smarty tira una Exception
    $html = str_replace('<totalpages />', '{nb}', $html);
    switch ($type) {
      case "header":
        $this->finalHtml;
        break;
      case "footer":
        $this->finalHtml;
        break;
    }
  }

  private function assignMetadata($metadata, $config, $ojsConfiguration)
  {
    foreach ($metadata as $key => $value) {
      if ($key === 'abstract_texts') {
        $value = str_replace('<br />', ' ', $value);
        $value = str_replace('<p>', '', $value);
        $value = str_replace('</p>', '', $value);
      }

      $this->privateTemplateManager->assign($key, $value);
      $this->publicTemplateManager->assign($key, $value);
    }

    // foreach ($ojsConfiguration as $key => $value) { # Lo dejo comentado ya que por ahora solo contiene configuración para la creación del PDF, no es necesario esto
    //   $this->templateManager->assign($key, $value);
    // }

    $licenses = $config->getLicenseConfig();
    $licenseName = array_search($metadata['license_url'], $licenses['links']);
    $licenseImg = $licenses['logos'][$licenseName];

    $this->publicTemplateManager->assign('license_logo', $licenseImg);
    $this->publicTemplateManager->assign('authors', $metadata['authors']);
    $this->publicTemplateManager->assign('orcid_logo', $config->getOrcidLogo());
    $this->publicTemplateManager->assign('images', $config->getImages());

    $this->privateTemplateManager->assign('license_logo', $licenseImg);
    $this->privateTemplateManager->assign('authors', $metadata['authors']);
    $this->privateTemplateManager->assign('orcid_logo', $config->getOrcidLogo());
    $this->privateTemplateManager->assign('images', $config->getImages());

    $baseFunctions = $this->publicTemplateManager->getTemplateDir()[0] . "/baseFunctions.tpl";

    $this->privateTemplateManager->assign('baseFunctions', $baseFunctions);
    $this->publicTemplateManager->assign('baseFunctions', $baseFunctions);
  }

  private function defaultProcessing($filepath) { # Este método sirve para renderizar cualquier cosa genérica que use metadatos  
    $html = $this->templateManager->fetch($filepath);
    $this->finalHtml .= $html;
  }

  private function processReferences($xpath, $dom, $htmlString, $config, $citeProc, $path)
  {
    $referencesAPA = $citeProc->getRawReferences();

    $referencesNodes = $xpath->evaluate('//div[contains(@class,"references-section")]//li');
    $references = HTMLProcessingService::setReferencesAnchors($referencesAPA, $referencesNodes); # Agrego los tags <a> vacíos para las redirecciones de referencias

    $htmlString = $dom->saveHTML();

    foreach ($xpath->query('//a[contains(@class, "bibr")]') as $a) { # Agrego los tags <a> a las citas
      HTMLProcessingService::processCitations($a, $dom, "citation_");
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

    HTMLProcessingService::processReferences($referencesSection, $references, $dom);

    $htmlString = $dom->saveHTML();

    $this->writeReferences($htmlString, 'references-section', $path, 'references'); # Escribo las references
  }

  public function processFootnotes($xpath, $dom, $htmlString, $config, $citeProc, $path)
  {
    $footnotesNodes = $xpath->evaluate('//div[contains(@class,"footnotes-container")]//div');
    $footnotes = HTMLProcessingService::setFootnotesAnchors($footnotesNodes); # Same con footnotes

    $htmlString = $dom->saveHTML();

    foreach ($xpath->query('//div[contains(@class, "footnote-item")]') as $a) { # Agrego los tags <a> a las citas footnote
      HTMLProcessingService::processCitations($a, $dom, "footnote_");
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

    HTMLProcessingService::processFootnotes($footnotesSection, $footnotes, $dom);

    $htmlString = $dom->saveHTML();

    $this->writeReferences($htmlString, 'footnotes-container', $path, 'footnotes'); # Escibo las footnotes
  }

  private function writeReferences($htmlString, $busqueda, $path)
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

      $r = HTMLProcessingService::processExternalLinks($newDoc);

      $html = $this->templateManager->fetch($path);
      $r = $html . $r;

      $this->finalHtml .= $r;
    }
  }

  private function processBody($xpath, $dom, $htmlString, $config, $citeProc, $path)
  {
    $referencesNodes = $xpath->evaluate('//a[contains(@class, "bibr")]'); # Procesar todas las citas, incluso si son múltiples
    foreach ($referencesNodes as $node) {
      HTMLProcessingService::citeToLink($node, $dom, $xpath, $config);
    }

    $footnotesNodes = $xpath->evaluate('//a[contains(@class, "fn")]'); # Same con footnotes
    foreach ($footnotesNodes as $node) {
      HTMLProcessingService::footnoteToLink($node, $dom);
    }

    $tableNodes = $xpath->evaluate('//a[contains(@class, "table")]'); # Citas a tablas
    foreach ($tableNodes as $node) {
      HTMLProcessingService::tableToLink($node, $dom);
    }

    $figNodes = $xpath->evaluate('//a[contains(@class, "fig")]'); # Citas a figuras
    foreach ($figNodes as $node) {
      HTMLProcessingService::figureToLink($node, $dom);
    }

    $htmlString = $dom->saveHTML();

    $bodyDom = new \DOMDocument('1.0', 'utf-8');
    $bodyDom->loadHTML($htmlString);
    $bodyXpath = new \DOMXPath($bodyDom);

    HTMLProcessingService::addTableReturnArrows($bodyDom, $bodyXpath);
    HTMLProcessingService::addFigureReturnArrows($bodyDom, $bodyXpath);

    $referencesSection = $bodyXpath->query('//div[contains(@class,"references-section")]');
    $referencesSection = $referencesSection->item(0);
    $footnotesSection = $bodyXpath->query('//div[contains(@class, "footnotes-container")]');
    $footnotesSection = $footnotesSection->item(0);

    HTMLProcessingService::setTablesClass($bodyXpath, 'table'); # A todas les asigno la clase "table" para no pisar el CSS del footer/header
    HTMLProcessingService::setTablesClass($bodyXpath, 'td');
    HTMLProcessingService::setTablesClass($bodyXpath, 'tr');
    HTMLProcessingService::setTablesClass($bodyXpath, 'th');

    /*
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
    */

    $isolatedBody = '<div class="article-body">';
    $isolatedBody = $bodyDom->saveHTML();
    $html = $this->templateManager->fetch($path);
    $isolatedBody = $html . $isolatedBody . "</div>";

    $this->finalHtml .= $isolatedBody;
  }

  public static function test()
  {
    return "hello world, leito was here";
  }
}
