<?php

namespace JATSParser\TemplateHandler\PDF;

use DOMDocument;
use PKP\core\JSONMessage;

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
  }

  private function whereToLook($selectedTemplate, $file)
  {

    $privateDir = $this->privateTemplateManager->getTemplateDir()[0];
    $publicDir = $this->publicTemplateManager->getTemplateDir()[0];

    if (file_exists("$privateDir/$selectedTemplate/$file")) {
      $this->templateManager = $this->privateTemplateManager;
      return $privateDir;
    }

    $this->templateManager = $this->publicTemplateManager;
    return $publicDir;
  }

  public function buildPDF($pdf, $htmlString, $xpath, $dom, $citeProc, $config, $metadata, $selectedTemplate, $ojsConfiguration)
  {
    $templatesDir = $this->publicTemplateManager->getTemplateDir()[0]; # Primero busco el catálogo, el cual se habló que NO puede ser sobreescribible. Si se quiere modificar se deben armar una plantilla nueva de 0.
    $error = "";
    $catalogDir = "$templatesDir/$selectedTemplate/catalog.xml";

    if (!file_exists($catalogDir)) {
      $error .= "Catálogo no encontrado \n";
    }

    $content = trim(file_get_contents("$templatesDir/$selectedTemplate/catalog.xml"));
    $xml = simplexml_load_string($content);
    $catalog = json_decode(json_encode($xml), true);

    # Después verifico la existencia del arcivo opcional de CSS Extra el cual tiene la posibilidad de sobreescribir todo el CSS del PDF, por lo tanto lo hago acá ya que el catálogo NO es modificable
    $templatesDir = $this->privateTemplateManager->getTemplateDir()[0];
    $file = "$templatesDir/$selectedTemplate/extraCSS.css";
    if (file_exists($file)) {
      $this->publicTemplateManager->assign('extraCSS', $file);
      $this->privateTemplateManager->assign('extraCSS', $file);
      # Nota: Todos los archivos que incluyan su propio CSS deben incluir (posteriormente) este de extraCSS.css. ¿Por qué? Hay una mejor explicación en el importCSS.tpl,
      # Resumidamente, al casi todo lo que viene de JATSParser usar tags sin clases o con clases variantes, es imposible asignarlas en el CSS, por lo tanto los estilos que pisan
      # deben estar incluídos en todos lados para funcionar correctamente, sino, solo modificaría el header y footer
      # Esto último pasa ya que el body, frontpage, references y footnotes se escriben de antemano, con el CSS aplicado hasta ese momento.
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
        if (file_exists($currentFileData['filepath'])) {
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
        // return new JSONMessage(
        //     false,
        //     __('plugins.generic.jatsParser.file.not.found')
        // );
      }

      # $margins = $this->setCustomMargins($currentFileData['filepath']);
      # $pdf->WriteHTML($margins); # Esta función permite la utilización de márgenes propios en cada parte del PDF, pero se rompe header y footer. Dejo comentado el uso pero no elimino la lógica por si a futuro la librería lo arregla

      $pdf->SetHTMLHeader(''); # Desactivo el Header
      $pdf->SetHTMLFooter(''); # Desactivo el Footer

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
            // return new JSONMessage(
            //   false,
            //   __('plugins.generic.jatsParser.file.not.found')
            // );
          } else {
            $fileUses[] = $usesFileData;
          }
        } else {
          $error .= "Artefacto $use no encontrado \n";
          // return new JSONMessage(
          //     false,
          //     __('plugins.generic.jatsParser.file.not.found')
          // );
        }
      }

      foreach ($fileUses as $use) {
        if (array_key_exists($use['role'], $this::USE_MAP)) {
          $fn = $this::USE_MAP[$use['role']];
          $this->$fn($use['filepath'], $pdf, $use['role']);
        } else {
          $this->genericProcessing($use['filepath'], $pdf); # Renderiza y escribe, por eso reuso el procesamiento y no es un método aparte
        }
      }

      if (array_key_exists($currentFileData['type'], $this::PROCESS_MAP)) {
        $fn = $this::PROCESS_MAP[$currentFileData['type']];
        $this->$fn($xpath, $dom, $htmlString, $pdf, $config, $citeProc, $currentFileData['filepath']);
      } else {
        $this->genericProcessing($pdf, $currentFileData['filepath']);
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

    $html = str_replace('<pagenumber/>', '{PAGENO}', $html);
    $html = str_replace('<totalpages/>', '{nb}', $html);

    switch ($type) {
      case "header":
        $pdf->SetHTMLHeader($html, 'O');
        break;
      case "footer":
        $pdf->SetHTMLFooter($html, 'O');
        break;
    }
  }

  private function assignMetadata($metadata, $config, $ojsConfiguration)
  {
    foreach ($metadata as $key => $value) {
      if ($key === 'abstract_texts') { # Desde JATSParser me llegan con estos tags que no son visibles en el PDF pero generan inconsistencias o comportamiento no deseado, por eso lo elimino con lo siguiente:
        $value = str_replace('<br />', ' ', $value);
        $value = str_replace('<br/>', ' ', $value);
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

  private function genericProcessing($pdf, $filepath)
  { # Este método sirve para renderizar cualquier cosa genérica que use metadatos  
    $html = $this->templateManager->fetch($filepath);

    $this->checkPageBreak($html, $pdf);
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

      $html = $this->templateManager->fetch($path);
      $r = $html . $r;

      $this->checkPageBreak($html, $pdf);
      $pdf->WriteHTML($r);
    }
  }

  private function processBody($xpath, $dom, $htmlString, $pdf, $config, $citeProc, $path)
  {
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

    $isolatedBody = '<div class="article-body">';
    $isolatedBody = $bodyDom->saveHTML();
    $html = $this->templateManager->fetch($path);
    $isolatedBody = $html . $isolatedBody . "</div>";

    $this->checkPageBreak($html, $pdf);
    $pdf->writeHTML($isolatedBody);

    return $htmlString;
  }

  private static function staticWhereToLook($selectedTemplate, $file, $plugin, $fileManager, $journalId) # No es que me encante duplicar el método, pero sino implica rehacer mucha lógica para que el original sea estático
  {

    $privateDir = $fileManager->getBasePath() . "/journals/$journalId/jatsParser_templates";
    $publicDir = $plugin->getPluginPath() . "/templates/SUMARC";

    if (file_exists("$privateDir/$selectedTemplate/$file")) {
      return $privateDir;
    }

    return $publicDir;
  }

  public static function checkTemplateIntegrity($selectedTemplate, $plugin, $fileManager, $journalId)
  {
    $templatesDir = $plugin->getPluginPath() . "/templates/SUMARC";
    $catalogDir = "$templatesDir/$selectedTemplate/catalog.xml";

    if (!file_exists($catalogDir)) {
      return false;
    }

    $content = trim(file_get_contents($catalogDir));
    $xml = simplexml_load_string($content);
    $catalog = json_decode(json_encode($xml), true);

    foreach ($catalog['build']['item'] as $part) {
      $currentFile = $catalog[$part]['file'];
      $templatesDir = self::staticWhereToLook($selectedTemplate, $currentFile, $plugin, $fileManager, $journalId);
      $currentFileData = [
        'filepath' => "$templatesDir/$selectedTemplate/$currentFile",
        'type' => $part
      ];
      $fileUses = [];
      if (!file_exists($currentFileData['filepath'])) {
        return false;
      }

      $uses = (array) (isset($catalog[$part]['uses']['use']) ? $catalog[$part]['uses']['use'] : []);

      foreach ($uses as $use) {
        if (is_string($use) && isset($catalog[$use]) && isset($catalog[$use]['file'])) { # Más chequeos por la conversión de XML...
          $usesFile = $catalog[$use]['file'];
          $templatesDir = self::staticWhereToLook($selectedTemplate, $currentFile, $plugin, $fileManager, $journalId);
          $usesFileData = [
            'filepath' => "$templatesDir/$selectedTemplate/$usesFile",
            'type' => $use,
            'role' => $catalog[$use]['role'],
          ];
          if (!file_exists($usesFileData['filepath'])) {
            return false;
          } else {
            $fileUses[] = $usesFileData;
          }
        } else {
          return false;
        }
      }
    }

    return true;
  }

  private function checkPageBreak($html, $pdf) {
    if((str_contains($html, '<pagebreak />')) || (str_contains($html, '<pagebreak/>'))) {
      $pdf->AddPage();
    }
  }

  public static function getTemplatePartsAndLocation($selectedTemplate, $plugin, $fileManager, $journalId)
  {
    $templatesDir = $plugin->getPluginPath() . "/templates/SUMARC";
    $catalogDir = "$templatesDir/$selectedTemplate/catalog.xml";
    $filesInformation = [];

    if(!self::checkTemplateIntegrity($selectedTemplate, $plugin, $fileManager, $journalId)) return; # Primero verifico la integirdad de la template
    # Si está todo en orden, me creo el array con la información de cada archivo en uso (tipo, directorio (público o privado) y el nombre)

    # El tipo me indica y sirve como "label" en la tabla, "Header", "Frontpage", etc.
    # El directorio me indica si se está usando el directorio privado, es decir, fue sobreescrito, o el directorio público (es el original)
    # El nombre me sirve para poder nombrar el archivo en caso de que el usuario desee subir uno, de esta manera puedo armar la ruta y darle el nombre apropiado

    # No es la mejor implementación en lo absoluto, pero estas cosas fueron surgiendo de manera informal y no tengo el tiempo de reescribir toda la lógica previa

    $content = trim(file_get_contents($catalogDir));
    $xml = simplexml_load_string($content);
    $catalog = json_decode(json_encode($xml), true);

    foreach ($catalog['build']['item'] as $part) {
      $currentFile = $catalog[$part]['file'];
      $templatesDir = self::staticWhereToLook($selectedTemplate, $currentFile, $plugin, $fileManager, $journalId);
      $currentFileData = [
        'filepath' => "$templatesDir/$selectedTemplate/$currentFile",
        'type' => $part
      ];

      $filesInformation[$currentFileData['type']] = [
        'using' => $templatesDir,
        'public' => self::isPrivate($templatesDir, $plugin),
        'filename' => $currentFile,
      ];

      $uses = (array) (isset($catalog[$part]['uses']['use']) ? $catalog[$part]['uses']['use'] : []);

      foreach ($uses as $use) {
        if (is_string($use) && isset($catalog[$use]) && isset($catalog[$use]['file'])) {
          $usesFile = $catalog[$use]['file'];
          $templatesDir = self::staticWhereToLook($selectedTemplate, $currentFile, $plugin, $fileManager, $journalId);
          $usesFileData = [
            'filepath' => "$templatesDir/$selectedTemplate/$usesFile",
            'type' => $use,
            'role' => $catalog[$use]['role'],
          ];

          $filesInformation[$usesFileData['type']] = [
            'using' => $templatesDir,
            'public' => self::isPrivate($templatesDir, $plugin),
            'filename' => $usesFile,
          ];
        }
      }
    }

    return $filesInformation;
  }

  private static function isPrivate($templatesDir, $plugin) {
    return str_contains($templatesDir, $plugin->getPluginPath());
  }

  public static function test()
  {
    return "hello world, leito was here";
  }
}
