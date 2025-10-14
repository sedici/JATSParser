<?php

namespace JATSParser\TemplateHandler;

use DOMDocument;

class PDFProcessingService
{
  public function citeToLink($node, $dom, $xpath, $config)
  {
    $this->replaceCitationsContent($xpath, $config);

    $text = trim($node->textContent, '[\]'); # Elimino los corcheted de cada cita, [1], [1,2]
    $numbers = explode(';', $text); # Guardo los números de la cita sin la coma en un array, [1], "[1, 2]"
    $refs = preg_split('/\s+/', $node->getAttribute('href')); # Guardo los href, #parser_0, #parser_0 parser_1
    $refs = array_map(function ($ref) {
      return str_replace('#', '', $ref);
    }, $refs); # Elimino el # del href, ya que solo el primero lo tiene (en caso de ser más de uno)

    $fragment = $dom->createDocumentFragment();
    #$fragment->appendChild($dom->createTextNode('[')); # Lo dejo comentado porque en APA no se utilizan los [ ]

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

  public function footnoteToLink($node, $dom)
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

  public function setReferencesAnchors($referencesAPA, $referencesNodes)
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

  public function setFootnotesAnchors($footnotesNodes)
  { # Creo el HTML para las footnotes
    for ($i = 0; $i < count($footnotesNodes); $i++) {
      $id = str_replace('fn-', '', $footnotesNodes[$i]->getAttribute('id'));

      $tempDom = new DOMDocument();
      $tempNode = $tempDom->importNode($footnotesNodes[$i], true);
      $tempDom->appendChild($tempNode);

      $footnotes[$i] = ["id" => $id, "text" => $tempDom->saveHTML()];
    }
    return $footnotes;
  }

  public function processCitations($a, $dom, $type)
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

  public function processReferences($referencesSection, $references, $dom)
  {
    $listContainer = $dom->createElement('ul');
    foreach ($references as $reference) {
      $li = $dom->createElement('li');

      $newNode = $dom->createElement('a', '');
      $newNode->setAttribute('name', $reference['id']);
      $newNode->setAttribute('id', $reference['id']);
      $li->appendChild($newNode);

      $ref = $dom->createDocumentFragment();
      $ref->appendXML($reference['text']);
      $li->appendChild($ref);

      $href = $dom->createElement('a', ' ↑');
      $href->setAttribute('href', '#citation_' . $reference['id']);
      $li->appendChild($href);

      $listContainer->appendChild($li);
    }
    
    if($referencesSection) $referencesSection->appendChild($listContainer);
  }

  public function replaceCitationsContent(\DOMXPath $xpath, $config)
  {
    //Process reference citations
    $supportedCitationStyles = $config::getSupportedCustomCitationStyles();
    $actualCitationStyle = $config->getCitationStyle();
    if ($supportedCitationStyles && in_array(strtolower($actualCitationStyle), $supportedCitationStyles)) {
      $publicationId = $config->getPublicationId();
      $localeKey = $config->getLocaleKeyConfig();

      //Get citations from database ONLY for APA style (for now)
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
}
