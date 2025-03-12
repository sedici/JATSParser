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

		$tableNodes = $xpath->evaluate('//table');
		foreach ($tableNodes as $tableNode) {
			$tableNode->setAttribute('border', '1');
			$tableNode->setAttribute('cellpadding', '2');
		}

		$captionNodes = $xpath->evaluate('//figure/p[@class="caption"]|//table/caption');
		foreach ($captionNodes as $captionNode) {
			$captionParts = $xpath->evaluate('span[@class="label"]|span[@class="title"]', $captionNode);
			foreach ($captionParts as $captionPart) {
				$emptyTextNode = $dom->createTextNode(' ');
				$captionPart->appendChild($emptyTextNode);
			}
		}

		// TCPDF doesn't recognize display property, insert div
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
									$ref->setAttribute('style', 'color: #0066CC; font-weight: bold;'); 
									break;
								}
							}
						}
					}
				}
			}
		}

		// Remove redundant whitespaces before caption label
		$modifiedHtmlString = $dom->saveHTML();
		$modifiedHtmlString = preg_replace('/<caption>\s*/', '<br>' . '<caption>', $modifiedHtmlString);
		$modifiedHtmlString = preg_replace('/<p class="caption">\s*/', '<p class="caption">', $modifiedHtmlString);

		return $modifiedHtmlString;
	}

}