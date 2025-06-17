<?php namespace JATSParser\PDF;

require_once __DIR__ . '/../../../../classes/daos/CustomPublicationSettingsDAO.inc.php';

class PDFBodyHelper {

	/**
	 * @param string $htmlString
	 * @return string Preprocessed HTML string for TCPDF
	*/
	public static function _prepareForPdfGalley(string $htmlString, $config): string {

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
		self::processCitations($dom, $xpath, $config);
		self::processFootnotes($dom, $xpath); 
		self::processReferences($dom, $xpath);
		self::processFiguresCitations($dom, $xpath);
		self::processTableCitations($dom, $xpath);
		self::processBlockquotes($dom, $xpath); 
		self::addHrefAttributes($xpath);

		// Remove redundant whitespaces before caption label
		$modifiedHtmlString = $dom->saveHTML();
		$modifiedHtmlString = preg_replace('/<caption>\s*/', '<br>' . '<caption>', $modifiedHtmlString);
		$modifiedHtmlString = preg_replace('/<p class="caption">\s*/', '<p class="caption">', $modifiedHtmlString);

		error_log($modifiedHtmlString);

		return $modifiedHtmlString;
	}
	
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
			// Buscar span con clase 'label' dentro de la tabla y cambiar su contenido a 'Tabla'
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
	private static function processCitations(\DOMDocument $dom, \DOMXPath $xpath, $config): void {
		$supportedCitationStyles = $config::getSupportedCustomCitationStyles();
		$actualCitationStyle = $config->getCitationStyle();
		if ($supportedCitationStyles && in_array(strtolower($actualCitationStyle), $supportedCitationStyles)) {
			$publicationId = $config->getPublicationId();
			$localeKey = $config->getLocaleKeyConfig();

			//Get citations from database ONLY for APA style.
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
	}

	/**
	 * Add href attributes to links for styling
	 * 
	 * @param \DOMDocument $dom The DOM document
	 * @param \DOMXPath $xpath The XPath object for DOM traversal
	 */
	private static function addHrefAttributes(\DOMXPath $xpath): void {
		$refs = $xpath->evaluate('//a');
		foreach ($refs as $ref) {
			$ref->setAttribute('style', 'color: #0066CC; text-decoration: none;'); 
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
			// Create a table structure to ensure proper indentation in TCPDF
			$table = $dom->createElement('table');
			$table->setAttribute('width', '100%');
			$table->setAttribute('border', '0');
			$table->setAttribute('cellspacing', '0');
			$table->setAttribute('cellpadding', '0');
			$table->setAttribute('style', 'margin-top: 10px; margin-bottom: 10px; width: 100%;');
			
			$tr = $dom->createElement('tr');
			
			// Initial spacing cell - significantly increased to move line far to the right
			$tdInitial = $dom->createElement('td');
			$tdInitial->setAttribute('width', '45'); // Increased from 15 to 45
			$spacerInitial = $dom->createTextNode(' ');
			$tdInitial->appendChild($spacerInitial);
			
			// Left cell with blue vertical line
			$tdLeft = $dom->createElement('td');
			$tdLeft->setAttribute('width', '5');
			$tdLeft->setAttribute('style', 'border-right: 4px solid #4c9cd6;');
			$spacer = $dom->createTextNode(' ');
			$tdLeft->appendChild($spacer);
			
			// Middle spacing cell - reduced to minimum
			$tdMiddle = $dom->createElement('td');
			$tdMiddle->setAttribute('width', '10'); // Reduced from 25 to 10
			$tdMiddle->setAttribute('style', 'padding: 0;');
			$spacer2 = $dom->createTextNode(' ');
			$tdMiddle->appendChild($spacer2);
			
			// Right cell for content - adjusted width
			$tdRight = $dom->createElement('td');
			$tdRight->setAttribute('width', '88%'); // Adjusted to account for new widths
			$tdRight->setAttribute('style', 'padding-left: 5px; width: 72%;');
			
			// Extract paragraphs and citations
			$paragraphs = $xpath->evaluate('./p', $blockquote);
			$citations = $xpath->evaluate('./cite', $blockquote);
			
			// Add content paragraphs to content cell with proper styling
			foreach ($paragraphs as $paragraph) {
				$clone = $paragraph->cloneNode(true);
				// Add specific styling to paragraph text - ensure it takes full width
				if (!$clone->hasAttribute('style')) {
					$clone->setAttribute('style', 'margin: 3px 0; width: 100%; font-size: 1.20em;');
				} else {
					// Append width to existing style
					$currentStyle = $clone->getAttribute('style');
					$clone->setAttribute('style', $currentStyle . '; width: 100%; font-size: 1.05em;');
				}
				$tdRight->appendChild($clone);
			}
			
			// Only add citation if it exists
			if ($citations->length > 0) {
				foreach ($citations as $citation) {
					$citeDiv = $dom->createElement('div');
					$citeDiv->setAttribute('class', 'blockquote-attribution');
					$citeDiv->setAttribute('style', 'margin-top: 2px; font-style: italic; text-align: right; font-size: 1.05em;');
					
					$clone = $citation->cloneNode(true);
					$citeDiv->appendChild($clone);
					$tdRight->appendChild($citeDiv);
				}
			}
			
			// Assemble the table with all cells
			$tr->appendChild($tdInitial);
			$tr->appendChild($tdLeft);
			$tr->appendChild($tdMiddle);
			$tr->appendChild($tdRight);
			$table->appendChild($tr);
			
			// Replace the original blockquote with our table structure
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