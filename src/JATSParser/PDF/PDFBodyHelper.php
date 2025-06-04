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

		self::styleTables($xpath);
		self::formatCaptions($dom, $xpath);
		self::moveCaptionsForTCPDF($dom, $xpath);
		self::processCitations($dom, $xpath, $config);
		self::processFiguresCitations($dom, $xpath);
		self::processBlockquotes($dom, $xpath); // Add this line to process blockquotes
		self::addHrefAttributes($xpath);

		// Remove redundant whitespaces before caption label
		$modifiedHtmlString = $dom->saveHTML();
		$modifiedHtmlString = preg_replace('/<caption>\s*/', '<br>' . '<caption>', $modifiedHtmlString);
		$modifiedHtmlString = preg_replace('/<p class="caption">\s*/', '<p class="caption">', $modifiedHtmlString);

		return $modifiedHtmlString;
	}
	
	/**
	 * Apply styling to tables
	 * 
	 * @param \DOMXPath $xpath The XPath object for DOM traversal
	 */
	private static function styleTables(\DOMXPath $xpath): void {
		$tableNodes = $xpath->evaluate('//table');
		foreach ($tableNodes as $tableNode) {
			$tableNode->setAttribute('border', '1');
			$tableNode->setAttribute('cellpadding', '2');
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
	 * Add href attributes to links for styling
	 * 
	 * @param \DOMDocument $dom The DOM document
	 * @param \DOMXPath $xpath The XPath object for DOM traversal
	 */
	private static function addHrefAttributes(\DOMXPath $xpath): void {
		$refs = $xpath->evaluate('//a[@href]');
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

}