<?php

namespace JATSParser\TemplateHandler\PDF;

use DOMDocument;

abstract class PDFProcessingService
{
  public static function citeToLink($node, $dom, $xpath, $config)
  {
    self::replaceCitationsContent($xpath, $config);

    $text = trim($node->textContent, '[\]'); # Elimino los corcheted de cada cita, [1], [1,2]
    $numbers = explode(';', $text); # Guardo los números de la cita sin la coma en un array, [1], "[1, 2]"
    $refs = preg_split('/\s+/', $node->getAttribute('href')); # Guardo los href, #parser_0, #parser_0 parser_1
    $refs = array_map(function ($ref) {
      return str_replace('#', '', $ref);
    }, $refs); # Elimino el # del href, ya que solo el primero lo tiene (en caso de ser más de uno)

    $fragment = $dom->createDocumentFragment();
    # $fragment->appendChild($dom->createTextNode('[')); # Lo dejo comentado porque en APA no se utilizan los [ ]

    for ($i = 0; $i < count($numbers); $i++) {
      $anchorNode = $dom->createElement('a');
      $anchorNode->setAttribute('name', 'citation_' . $refs[$i]);
      $anchorNode->setAttribute('id', 'citation_' . $refs[$i]);
      $fragment->appendChild($anchorNode);

      $newNode = $dom->createElement('a', $numbers[$i]);
      $newNode->setAttribute('href', '#' . $refs[$i]);
      $fragment->appendChild($newNode);

      if ($i < count($numbers) - 1) {
        $fragment->appendChild($dom->createTextNode(';')); # Agrego los ; si es necesario
      }
    }

    #$fragment->appendChild($dom->createTextNode(']'));
    $node->parentNode->replaceChild($fragment, $node);
  }

  public static function footnoteToLink($node, $dom)
  {
    $numbers = explode(',', $node->textContent); # Guardo los números de la fn sin la coma en un array, [1], "[1, 2]"
    $refs = preg_split('/\s+/', $node->getAttribute('href')); # Guardo los href, #parser_0, #parser_0 parser_1
    $refs = array_map(function ($ref) {
      return str_replace('#', '', $ref);
    }, $refs); # Elimino el # del href, ya que solo el primero lo tiene (en caso de ser más de uno)

    $fragment = $dom->createDocumentFragment();

    for ($i = 0; $i < count($numbers); $i++) {
      $anchorNode = $dom->createElement('a');
      $anchorNode->setAttribute('name', 'citation_' . $refs[$i]);
      $anchorNode->setAttribute('id', 'citation_' . $refs[$i]);
      $fragment->appendChild($anchorNode);

      $sup = $dom->createElement('sup');
      $newNode = $dom->createElement('a', $numbers[$i]);
      $newNode->setAttribute('href', '#' . $refs[$i]);
      $sup->appendChild($newNode);
      $fragment->appendChild($sup);

      if ($i < count($numbers) - 1) {
        $fragment->appendChild($dom->createElement('sup', ',')); # Agrego las , si es necesario
      }
    }

    $node->parentNode->replaceChild($fragment, $node);
  }

  public static function setReferencesAnchors($referencesAPA, $referencesNodes)
  { # Creo el HTML para las references
    $references = [];
    for ($i = 0; $i < count($referencesNodes); $i++) {
      $id = $referencesNodes[$i]->getAttribute('id');

      $tempDom = new DOMDocument();
      $tempNode = $tempDom->importNode($referencesNodes[$i], true);
      $tempDom->appendChild($tempNode);

      $references[$i] = ["id" => $id, "text" => $tempDom->saveHTML()];
    }
    return $references;
  }

  public static function setFootnotesAnchors($footnotesNodes)
  { # Creo el HTML para las footnotes
    $footnotes = [];
    for ($i = 0; $i < count($footnotesNodes); $i++) {
      $id = str_replace('fn-', '', $footnotesNodes[$i]->getAttribute('id'));

      $tempDom = new DOMDocument();
      $tempNode = $tempDom->importNode($footnotesNodes[$i], true);
      $tempDom->appendChild($tempNode);

      $footnotes[$i] = ["id" => $id, "text" => $tempDom->saveHTML()];
    }
    return $footnotes;
  }

  public static function processCitations($a, $dom, $type)
  { # Agrego las tags <a> vacías de las citas
    $id = $a->getAttribute('href');
    $id = trim($id, '#');
    $id = trim($id, 'fn-');
    $id = $type . $id;

    $newTag = $dom->createElement('a', '');
    $newTag->setAttribute('name', $id);
    $newTag->setAttribute('id', $id);

    if ($a->nextSibling) {
      $a->parentNode->insertBefore($newTag, $a->nextSibling);
    } else {
      $a->parentNode->appendChild($newTag);
    }
  }

  public static function processFootnotes($footnotesSection, $footnotes, $dom)
  {
    $listContainer = $dom->createElement('ul');
    foreach ($footnotes as $footnote) {
      $li = $dom->createElement('li');

      $newNode = $dom->createElement('a', '');
      $newNode->setAttribute('name', $footnote['id']);
      $newNode->setAttribute('id', $footnote['id']);
      $li->appendChild($newNode);

      $ref = $dom->createDocumentFragment();
      $ref->appendXML($footnote['text']);

      # Buscar el primer div dentro del fragmento y agregar la flecha 
      $tempDom = new \DOMDocument();
      $tempDom->loadHTML('<?xml encoding="utf-8" ?>' . $footnote['text']);
      $divs = $tempDom->getElementsByTagName('div');
      if ($divs->length > 0) {
        $div = $divs->item(0);
        $arrowLink = $tempDom->createElement('a', ' ↑');
        $arrowLink->setAttribute('href', '#citation_' . $footnote['id']);
        $div->appendChild($arrowLink);
        $newHtml = '';
        foreach ($tempDom->getElementsByTagName('body')->item(0)->childNodes as $child) {
          $newHtml .= $tempDom->saveHTML($child);
        }
        $ref = $dom->createDocumentFragment();
        $ref->appendXML($newHtml);
      } else {
        # Si no hay div, agrega la flecha al final
        $arrowLink = $dom->createElement('a', ' ↑');
        $arrowLink->setAttribute('href', '#citation_' . $footnote['id']);
        $ref->appendChild($arrowLink);
      }

      $li->appendChild($ref);
      $listContainer->appendChild($li);
    }

    if ($footnotesSection) $footnotesSection->appendChild($listContainer);
  }

  public static function processReferences($referencesSection, $references, $dom)
  {
    $listContainer = $dom->createElement('ul');
    foreach ($references as $reference) {
      # Si el texto ya es un <li> con la referencia, se parsea y agrega las flechitas
      if (preg_match('/^<li[^>]*>.*<\/li>$/s', trim($reference['text']))) {
        $tempDom = new \DOMDocument();
        $tempDom->loadHTML('<?xml encoding="utf-8" ?>' . $reference['text']);
        $liNodes = $tempDom->getElementsByTagName('li');
        if ($liNodes->length > 0) {
          $li = $liNodes->item(0);
          # <a> vacío para navegación interna, SI NO SE USA NO ANDAN LOS HREF
          $anchor = $tempDom->createElement('a', '');
          $anchor->setAttribute('name', $reference['id']);
          $anchor->setAttribute('id', $reference['id']);
          $li->insertBefore($anchor, $li->firstChild);
          $arrowLink = $tempDom->createElement('a', ' ↑');
          $arrowLink->setAttribute('href', '#citation_' . $reference['id']);
          $li->appendChild($arrowLink);
          $importedLi = $dom->importNode($li, true);
          $listContainer->appendChild($importedLi);
          continue;
        }
      }
      # Si no es un <li>, usar el método anterior (nunca debería llegar a pasar, pero quien sabe)
      $li = $dom->createElement('li');
      $anchor = $dom->createElement('a', '');
      $anchor->setAttribute('name', $reference['id']);
      $anchor->setAttribute('id', $reference['id']);
      $li->appendChild($anchor);
      $ref = $dom->createDocumentFragment();
      $ref->appendXML($reference['text']);
      $li->appendChild($ref);
      $arrowLink = $dom->createElement('a', ' ↑');
      $arrowLink->setAttribute('href', '#citation_' . $reference['id']);
      $li->appendChild($arrowLink);
      $listContainer->appendChild($li);
    }
    if ($referencesSection) $referencesSection->appendChild($listContainer);
  }

  public static function replaceCitationsContent(\DOMXPath $xpath, $config)
  {
    $supportedCitationStyles = $config::getSupportedCustomCitationStyles();
    $actualCitationStyle = $config->getCitationStyle();
    if ($supportedCitationStyles && in_array(strtolower($actualCitationStyle), $supportedCitationStyles)) {
      $publicationId = $config->getPublicationId();
      $localeKey = $config->getLocaleKeyConfig();

      $customPublicationSettingsDAO = new \CustomPublicationSettingsDAO();
      $settings = $customPublicationSettingsDAO->getSetting($publicationId, 'jatsParser::citationTableData', $localeKey);

      if ($settings) {
        $refs = $xpath->evaluate('//a[@href]');
        foreach ($refs as $ref) {
          foreach ($settings['fileId'] as $fileId => $xrefData) {
            if (is_array($xrefData)) {
              foreach ($xrefData as $xrefId => $citationText) {
                if ($ref->getAttribute('id') === $xrefId) {
                  $ref->nodeValue = $citationText;
                  break;
                }
              }
            }
          }
        }
      }
    }
  }

  public static function processExternalLinks(\DOMDocument $dom)
  {
    $xpath = new \DOMXPath($dom);
    $externalLinks = $xpath->evaluate('//ext-link');
    foreach ($externalLinks as $link) {
      $a = $dom->createElement('a', $link->textContent);
      if ($link->hasAttribute('xlink:href')) {
        $a->setAttribute('href', $link->getAttribute('xlink:href'));
      }
      $link->parentNode->replaceChild($a, $link);
    }

    return $dom->saveHTML();
  }

  public static function tableToLink($node, $dom, $xpath = null)
  {
    $tableId  = ltrim($node->getAttribute('href'), '#'); // e.g. "T1"
    $anchorId = 'citation_table_' . bin2hex(random_bytes(4)); // ID único por cita

    $anchorNode = $dom->createElement('a');
    $anchorNode->setAttribute('name', $anchorId);
    $anchorNode->setAttribute('id', $anchorId);

    if ($node->nextSibling) {
      $node->parentNode->insertBefore($anchorNode, $node->nextSibling);
    } else {
      $node->parentNode->appendChild($anchorNode);
    }

    $node->setAttribute('data-citation-anchor', $anchorId);
  }

  public static function addTableReturnArrows($dom, $xpath)
  {
    foreach ($xpath->query('//table[@id]') as $tableNode) {
      $tableId = $tableNode->getAttribute('id');
      $citations = $xpath->query('//a[@data-citation-anchor and @href="#' . $tableId . '"]');
      if ($citations->length === 0) continue;

      $caption = $xpath->query('.//caption', $tableNode)->item(0);
      if (!$caption) {
        $caption = $dom->createElement('caption');
        if ($tableNode->firstChild) {
          $tableNode->insertBefore($caption, $tableNode->firstChild);
        } else {
          $tableNode->appendChild($caption);
        }
      }

      $arrowContainer = $dom->createElement('span');
      $arrowContainer->setAttribute('class', 'table-return-arrows');
      $caption->appendChild($arrowContainer);

      foreach ($citations as $citation) {
        $anchorId = $citation->getAttribute('data-citation-anchor');
        if (!$anchorId) continue;
        $arrow = $dom->createElement('a');
        $arrow->appendChild($dom->createTextNode(' ↑'));
        $arrow->setAttribute('href', '#' . $anchorId);
        $arrow->setAttribute('class', 'return-arrow');
        $arrowContainer->appendChild($arrow);
      }
    }
  }

  public static function setTablesClass($bodyXpath, $term)
  {
    $items = $bodyXpath->query('//' . $term);

    foreach ($items as $item) {
      $item->setAttribute('class', 'table');
    }

    return $items;
  }

  private function setCustomMargins($filepath, $templateManager)
  {
    $html = $templateManager->fetch($filepath);

    $dom = new DOMDocument('1.0', 'utf-8');
    $dom->loadHTML($html);
    $xpath = new \DOMXPath($dom);

    $margins = ['top', 'bottom', 'left', 'right'];
    $marginValues = ['top' => '25mm', 'bottom' => '30mm', 'left' => '20mm', 'right' => '20mm'];

    foreach ($margins as $margin) {
      $query = "//margin-$margin";
      $nodes = $xpath->query($query);
      foreach ($nodes as $node) {
        $size = $node->getAttribute('size');
        if ($size) {
          $marginValues[$margin] = $size;
        }
        if ($node->parentNode) {
          $node->parentNode->removeChild($node);
        }
      }
    }

    $style = "<style>
          @page {
              margin-top: {$marginValues['top']};
              margin-bottom: {$marginValues['bottom']};
              margin-left: {$marginValues['left']};
              margin-right: {$marginValues['right']};
          }
      </style>";

    return $style;
  }

  public static function checkPageBreak($html, $pdf) {
    if((str_contains($html, '<pagebreak />')) || (str_contains($html, '<pagebreak/>'))) {
      $pdf->addPage();
    }
    
    if((str_contains($html, '<break />')) || (str_contains($html, '<break/>'))) {
      $html = str_replace('<break />', '<pagebreak />', $html);
      $html = str_replace('<break/>', '<pagebreak />', $html);
    }

    return $html;
  }
}
