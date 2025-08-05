<?php namespace JATSParser\PDF;

require_once __DIR__ . '/../../../../classes/daos/CustomPublicationSettingsDAO.inc.php';

class PDFBodyHelper {

	/**
	 * @param string $htmlString
	 * @return string Preprocessed HTML string for TCPDF
	*/
	public static function _prepareForPdfGalley(string $htmlString, $config, $pdfTemplate, &$refs): string {

		$dom = new \DOMDocument('1.0', 'utf-8');
		$htmlHead = "\n";
		$htmlHead .= '<head>';
		$htmlHead .= "\t" . '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
		$htmlHead .= "\n";
		$htmlHead .= '</head>';
		$dom->loadHTML($htmlHead . $htmlString);

		// set style for figures and table
		$xpath = new \DOMXPath($dom);

		self::processTables($xpath);
		self::formatCaptions($dom, $xpath);
		self::moveCaptionsForTCPDF($dom, $xpath);
		//self::addLinks($dom, $xpath, $pdfTemplate);
		self::replaceCitationsContent($dom, $xpath, $config);
		self::processFootnotes($dom, $xpath); 
		self::processReferences($dom, $xpath);
		self::processFiguresCitations($dom, $xpath);
		self::processTableCitations($dom, $xpath);
		self::processBlockquotes($dom, $xpath); 
		self::processHrefElements($xpath);
		self::processExternalLinks($dom, $xpath);

		// Buscar todos los <li> dentro de .references-section
		$referencesNodes = $xpath->evaluate('//div[contains(@class,"references-section")]//li');
		foreach ($referencesNodes as $refNode) {
			$id = $refNode->getAttribute('id');
			$refs[$id] = $pdfTemplate->AddLink(); // lo vamos a llenar con AddLink() más adelante
		}

		//process all <a> elements with class "bibr" to replace them with {{LINK:refId:linkText}}
		foreach ($xpath->query('//a[contains(@class, "bibr")]') as $a) {
			$href = ltrim($a->getAttribute('href'), '#');
			if (isset($refs[$href])) {
				// Reemplazamos el <a> por texto plano que será reemplazado con TCPDF->Write más adelante
				$a->parentNode->replaceChild(
					$dom->createTextNode("{{LINK:$href:" . $a->nodeValue . "}}"),
					$a
				);				
			}
	
		}

		// Buscar todos Las footnotes <div><span> dentro de footnotes-container
		$footnotesNodes = $xpath->evaluate('//div[contains(@class,"footnotes-container")]//div');
		foreach ($footnotesNodes as $node) {
			$id = $node->getAttribute('id');
			if (strpos($id, 'fn-') === 0) {
				$id = substr($id, 3); // quitar 'fn-' si existe
			}
			$refs[$id] = $pdfTemplate->AddLink(); // lo vamos a llenar con AddLink() más adelante
		}

		error_log(print_r($refs, true));

		foreach ($xpath->query('//a[contains(@class, "fn")]') as $a) {
			$href = ltrim($a->getAttribute('href'), '#');
			error_log("PROCESANDO HREF:" . $href);
			error_log("PROCESANDO HREF:" . $href);
			error_log("PROCESANDO HREF:" . $href);
			error_log("PROCESANDO HREF:" . $href);
			error_log("PROCESANDO HREF:" . $href);
			error_log("PROCESANDO HREF:" . $href);
			error_log("PROCESANDO HREF:" . $href);
			if (isset($refs[$href])) {
				error_log("key " . $href . " SETEADA");
				// Reemplazamos el <a> por texto plano que será reemplazado con TCPDF->Write más adelante
				$a->parentNode->replaceChild(
					$dom->createTextNode("{{LINK:$href:" . $a->nodeValue . "}}"),
					$a
				);
			}
		}

		// Remove redundant whitespaces before caption label
		$modifiedHtmlString = $dom->saveHTML();
		$modifiedHtmlString = preg_replace('/<caption>\s*/', '<br>' . '<caption>', $modifiedHtmlString);
		$modifiedHtmlString = preg_replace('/<p class="caption">\s*/', '<p class="caption">', $modifiedHtmlString);

		return $modifiedHtmlString;
	}

	/*
	 
	private static function addLinks(\DOMDocument $dom, \DOMXPath $xpath, $pdfTemplate): void {
		$linkMap = [];

		// process all anchors in the document
		$anchors = $xpath->evaluate('//a[@href]');
		foreach ($anchors as $anchor) {
			$href = $anchor->getAttribute('href');
			// use the href content as the key
			if (!isset($linkMap[$href])) {
				$linkMap[$href] = $pdfTemplate->AddLink();
			}
			$anchor->setAttribute('data-tcpdf-link', $linkMap[$href]);
		}

		// process all elements with id attributes
		$refs = $xpath->evaluate('//*[@id]');
		foreach ($refs as $ref) {
			$id = $ref->getAttribute('id');
			if (isset($linkMap['#' . $id])) {
				$pdfTemplate->SetLink($linkMap['#' . $id]);
				$ref->setAttribute('data-tcpdf-setlink', $linkMap['#' . $id]);
			}
		}
	}
		/*
	
	/**
	 * Processing tables for styles and translations.
	 * 
	 * @param \DOMXPath $xpath The XPath object for DOM traversal
	 */
	private static function processTables(\DOMXPath $xpath): void {
		$tableNodes = $xpath->evaluate('//table');
		foreach ($tableNodes as $tableNode) {
			$tableNode->setAttribute('border', '1');
			$tableNode->setAttribute('cellpadding', '2');
			// Search span elements with class "label" inside the table
			$labelSpans = $xpath->evaluate('.//span[@class="label"]', $tableNode);
			foreach ($labelSpans as $span) {
				$spanContent = $span->textContent;
				if (preg_match('/\d+/', $spanContent, $matches)) {
					$tableNumber = $matches[0]; // Get the table number
					$translatedTableText = __('plugins.generic.jatsParser.table.title'); // Translate the table text if needed (e.g., "Table 1" for english or "Tabla 1" for spanish)
					$span->textContent = $translatedTableText . ' ' . $tableNumber; // Set the new content
 				}
			}
		}
	}

	/**
	 * Process figure citations in the document to translate them. For example, "Figure 1" to "Figura 1" in Spanish. 
	 * 
	 * @param \DOMDocument $dom The DOM document
	 * @param \DOMXPath $xpath The XPath object for DOM traversal
	 */
	private static function processFiguresCitations(\DOMDocument $dom, \DOMXPath $xpath): void {
		$figureCitationNodes = $xpath->evaluate('//a[@class="fig"]');
		foreach ($figureCitationNodes as $node) {
			$nodeContent = $node->textContent;
			if (preg_match('/\d+/', $nodeContent, $matches)) { //extract the figure number from the content
				$translatedFigureText = __('plugins.generic.jatsParser.figure.title'); // Translate the figure text if needed (e.g., "Figure 1" for english or "Figura 1" for spanish)
				$figureNumber = $matches[0]; // Get the figure number
				$node->textContent = $translatedFigureText . ' ' . $figureNumber;
			}
		}
	}

	/** 
	 * Process table citations in the document to translate them. For example, "Table 1" to "Tabla 1" in Spanish.
	 * 
	 * @param \DOMDocument $dom The DOM document
	 * @param \DOMXPath $xpath The XPath object for DOM traversal
	 */
	private static function processTableCitations(\DOMDocument $dom, \DOMXPath $xpath): void {
		$tableCitationNodes = $xpath->evaluate('//a[@class="table"]');
		foreach ($tableCitationNodes as $node) {
			$nodeContent = $node->textContent;
			if (preg_match('/\d+/', $nodeContent, $matches)) { //extract the table number from the content
				$translatedTableText = __('plugins.generic.jatsParser.table.title'); // Translate the table text if needed (e.g., "Table 1" for english or "Tabla 1" for spanish)
				$tableNumber = $matches[0]; // Get the table number
				$node->textContent = $translatedTableText . ' ' . $tableNumber;
			}
		}
	}
	
	/**
	 * Format captions for figures and tables
	 * 
	 * @param \DOMDocument $dom The DOM document
	 * @param \DOMXPath $xpath The XPath object for DOM traversal
	 */
	private static function formatCaptions(\DOMDocument $dom, \DOMXPath $xpath): void {
		$captionNodes = $xpath->evaluate('//figure/p[@class="caption"]|//table/caption');
		foreach ($captionNodes as $captionNode) {
			$captionParts = $xpath->evaluate('span[@class="label"]|span[@class="title"]', $captionNode);
			foreach ($captionParts as $captionPart) {
				$emptyTextNode = $dom->createTextNode(' ');
				$captionPart->appendChild($emptyTextNode);
			}
		}
	}
	
	/**
	 * Move table captions for TCPDF compatibility
	 * 
	 * @param \DOMDocument $dom The DOM document
	 * @param \DOMXPath $xpath The XPath object for DOM traversal
	 */
	private static function moveCaptionsForTCPDF(\DOMDocument $dom, \DOMXPath $xpath): void {
		$tableCaptions = $xpath->evaluate('//table/caption');
		foreach ($tableCaptions as $tableCaption) {
			/* @var $tableNode \DOMNode */
			$tableNode = $tableCaption->parentNode;
			$divNode = $dom->createElement('div');
			$divNode->setAttribute('class', 'caption');
			$nextToTableNode = $tableNode->nextSibling;
			if ($nextToTableNode) {
				$tableNode->parentNode->insertBefore($divNode, $nextToTableNode);
			}
			$divNode->appendChild($tableCaption);
		}
	}
	
	/**
	 * Process citations based on citation style
	 * 
	 * @param \DOMDocument $dom The DOM document
	 * @param \DOMXPath $xpath The XPath object for DOM traversal
	 * @param object $config The configuration object
	 */
	private static function replaceCitationsContent(\DOMDocument $dom, \DOMXPath $xpath, $config): void {
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
				foreach($refs as $ref) {
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

		//Process footnotes citations for superscript links
		$fnLinks = $xpath->evaluate('//a[contains(@class, "fn")]');
		foreach ($fnLinks as $fnLink) {
			$textContent = $fnLink->textContent; 
			
			while ($fnLink->firstChild) {
        		$fnLink->removeChild($fnLink->firstChild);
    		}

			$sup = $fnLink->ownerDocument->createElement('sup', $textContent);
			$fnLink->appendChild($sup);
		}
	}

	/**
	 * Add href attributes to links for styling
	 * 
	 * @param \DOMDocument $dom The DOM document
	 * @param \DOMXPath $xpath The XPath object for DOM traversal
	 */
	private static function processHrefElements(\DOMXPath $xpath): void {
		//process all links in the document(including urls - citations)
		$refs = $xpath->evaluate('//a');
		foreach ($refs as $ref) {
			$ref->setAttribute('style', 'color: #0066CC; text-decoration: none;'); 
		}
	}

	private static function processExternalLinks(\DOMDocument $dom, \DOMXPath $xpath): void {
		// Process external links to ensure they are styled correctly and converted to <a> elements for a correct styling process in TCPDF
		$externalLinks = $xpath->evaluate('//ext-link');
		foreach ($externalLinks as $link) {
			// Create a new <a> element with the link text
			$a = $dom->createElement('a', $link->textContent);
			// Copy the class attribute if it exists
			if ($link->hasAttribute('xlink:href')) {
				$a->setAttribute('href', $link->getAttribute('xlink:href'));
			}
			// Add the class attribute if it exists
			$a->setAttribute('style', 'color: #0066CC; text-decoration: none;');
			// Replace the <ext-link> element with the new <a> element
			$link->parentNode->replaceChild($a, $link);
		}
	}

	/**
	 * Process blockquotes to improve spacing and layout in PDF
	 * 
	 * @param \DOMDocument $dom The DOM document
	 * @param \DOMXPath $xpath The XPath object for DOM traversal
	 */
	private static function processBlockquotes(\DOMDocument $dom, \DOMXPath $xpath): void {
		$blockquotes = $xpath->evaluate('//blockquote');
		foreach ($blockquotes as $blockquote) {
			// 1) Creamos la tabla contenedora
			$table = $dom->createElement('table');
			$table->setAttribute('width', '100%');
			$table->setAttribute('border', '0');
			$table->setAttribute('cellspacing', '0');
			$table->setAttribute('cellpadding', '0');
			// margin-top para el espacio superior; margin-bottom ya no es necesario
			$table->setAttribute('style', 'margin-top: 10px; width: 100%;');

			// 2) Construimos la fila principal con las 4 celdas
			$tr = $dom->createElement('tr');

			// 2.1) Celda inicial para desplazar la línea hacia la derecha
			$tdInitial = $dom->createElement('td');
			$tdInitial->setAttribute('width', '45');
			$tdInitial->appendChild($dom->createTextNode(' '));

			// 2.2) Celda de la línea vertical azul
			$tdLeft = $dom->createElement('td');
			$tdLeft->setAttribute('width', '5');
			$tdLeft->setAttribute('style', 'border-right: 4px solid #4c9cd6;');
			$tdLeft->appendChild($dom->createTextNode(' '));

			// 2.3) Celda intermedia de separación
			$tdMiddle = $dom->createElement('td');
			$tdMiddle->setAttribute('width', '10');
			$tdMiddle->setAttribute('style', 'padding: 0;');
			$tdMiddle->appendChild($dom->createTextNode(' '));

			// 2.4) Celda de contenido (texto y atribución)
			$tdRight = $dom->createElement('td');
			$tdRight->setAttribute('width', '88%');
			$tdRight->setAttribute('style', 'padding-left: 5px; width: 72%;');

			// 3) Movemos párrafos dentro de tdRight
			$paragraphs = $xpath->evaluate('./p', $blockquote);
			foreach ($paragraphs as $paragraph) {
				$clone = $paragraph->cloneNode(true);
				$style = $clone->hasAttribute('style')
					? $clone->getAttribute('style') . '; width: 100%; font-size: 0.95em;'
					: 'margin: 3px 0; width: 100%; font-size: 0.95em;';
				$clone->setAttribute('style', $style);
				$tdRight->appendChild($clone);
			}

			// 4) Movemos las citas (<cite>) si existen
			$citations = $xpath->evaluate('./cite', $blockquote);
			if ($citations->length > 0) {
				foreach ($citations as $citation) {
					$citeDiv = $dom->createElement('div');
					$citeDiv->setAttribute('class', 'blockquote-attribution');
					$citeDiv->setAttribute(
						'style',
						'margin-top: 2px; font-style: italic; text-align: right; font-size: 0.95em;'
					);
					$clone = $citation->cloneNode(true);
					$citeDiv->appendChild($clone);
					$tdRight->appendChild($citeDiv);
				}
			}

			// 5) Montamos la fila y la añadimos a la tabla
			$tr->appendChild($tdInitial);
			$tr->appendChild($tdLeft);
			$tr->appendChild($tdMiddle);
			$tr->appendChild($tdRight);
			$table->appendChild($tr);

			// 6) Fila espaciadora para el margen inferior (10px)
			$spacerRow  = $dom->createElement('tr');
			$spacerCell = $dom->createElement('td');
			$spacerCell->setAttribute('colspan', '4');
			$spacerCell->setAttribute('height', '10'); // mismo valor que margin-top
			$spacerRow->appendChild($spacerCell);
			$table->appendChild($spacerRow);

			// 7) Reemplazamos el <blockquote> original por la tabla
			$blockquote->parentNode->replaceChild($table, $blockquote);
		}
	}

	/**
	 * Process footnotes to apply styles and improve layout in PDF
	 * 
	 * @param \DOMDocument $dom The DOM document
	 * @param \DOMXPath $xpath The XPath object for DOM traversal
	 */
	private static function processFootnotes(\DOMDocument $dom, \DOMXPath $xpath): void {
		// Find the footnotes container
		$footnoteContainers = $xpath->evaluate('//div[@class="footnotes-container"]');
		if ($footnoteContainers->length === 0) {
			return; // No footnotes to process
		}
		
		// Style the container
		foreach ($footnoteContainers as $container) {
			$container->setAttribute('style', 'margin-top: 3em; border-top: 1px solid #ddd; padding-top: 1em;');
		}
		
		// Style individual footnotes
		$footnoteItems = $xpath->evaluate('//div[@class="footnote-item"]');
		foreach ($footnoteItems as $item) {
			$item->setAttribute('style', 'display: flex; flex-direction: row; margin-bottom: 1em; font-size: 1.05em; align-items: flex-start;');
			
			// Style the footnote label
			$labelNodes = $xpath->evaluate('.//span[@class="footnote-label"]', $item);
			if ($labelNodes->length > 0) {
				$labelNode = $labelNodes->item(0);
				$labelNode->setAttribute('style', 'display: inline-block; color: #31849b; font-weight: bold; margin-right: 0.8em; min-width: 1.5em; text-align: left;');
			}
			
			// Style the footnote content
			$contentNodes = $xpath->evaluate('.//span[@class="footnote-content"]', $item);
			if ($contentNodes->length > 0) {
				$contentNode = $contentNodes->item(0);
				$contentNode->setAttribute('style', 'display: inline-block; flex: 1; text-align: left;');
			}
		}
	}

	/**
	 * Process references list to apply styles and improve layout in PDF
	 * 
	 * @param \DOMDocument $dom The DOM document
	 * @param \DOMXPath $xpath The XPath object for DOM traversal
	 */
	private static function processReferences(\DOMDocument $dom, \DOMXPath $xpath): void {
		// Find reference containers
		$referenceContainers = $xpath->evaluate('//div[@class="references-section"]');
		if ($referenceContainers->length === 0) {
			return; // No references to process
		}
		
		// Style the reference section container
		foreach ($referenceContainers as $container) {
			$container->setAttribute('style', 'margin-top: 3em; border-top: 1px solid #ddd; padding-top: 1em;');
		}
		
		// Process the citation list container
		$citationLists = $xpath->evaluate('//ol[@class="citation-list"]|//div[@class="citation-list"]');
		foreach ($citationLists as $list) {
			$citationStyle = $list->getAttribute('data-style');
			
			if ($list->nodeName === 'ol') {
				$list->setAttribute('style', 'margin-top: 2em; padding-left: 2em;');
			} else {
				$list->setAttribute('style', 'margin-top: 2em;');
			}
			
			// Style individual citation items based on citation style
			$items = $xpath->evaluate('./li[@class="citation-item"]|./div[@class="citation-item"]', $list);
			foreach ($items as $item) {
				// Apply different styles based on citation style
				switch($citationStyle) {
					case 'apa':
						$item->setAttribute('style', 'margin-left: 0; padding-left: 7em; margin-bottom: 2.5em; line-height: 1.1; text-align: left; padding-bottom: 0.5em;');
						break;
					case 'ieee':
					case 'vancouver':
						$item->setAttribute('style', 'margin-bottom: 1.5em; line-height: 1.1; text-align: left;');
						break;
					default:
						$item->setAttribute('style', 'margin-bottom: 1.5em; line-height: 1.1; text-align: left;');
				}
				
				// Style URLs within the citations
				$urlSpans = $xpath->evaluate('.//span[@class="citation-url"]', $item);
				foreach ($urlSpans as $url) {
					$url->setAttribute('style', 'color: #31849b; word-wrap: break-word;');
				}
			}
		}
	}

}