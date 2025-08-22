<?php namespace JATSParser\HTML\UnlpHTML;

use JATSParser\HTML\Par as Par;
use JATSParser\Body\Table as JATSTable;
use JATSParser\Body\Row as JATSRow;
use JATSParser\HTML\Cell as Cell;
use JATSParser\Body\Cell as JATSCell;
use JATSParser\HTML\Text as HTMLText;

class Table extends \DOMElement {

	public function __construct() {

		parent::__construct("table");

	}

	public function setContent(JATSTable $jatsTable) {
		// Set table id for table-wrap. Needed for links from references to the table
		$this->setAttribute("id", $jatsTable->getId());

		 // Get the parent node to add elements before and after the table
		$parentNode = $this->parentNode;
		if ($parentNode) {
		    // Add spacing BEFORE the caption (between previous content and table title)
		    $topSpacer = $this->ownerDocument->createElement("p");
		    $parentNode->insertBefore($topSpacer, $this);
		    
			// Create caption div BEFORE the table
			$captionDiv = $this->ownerDocument->createElement("div");
			$captionDiv->setAttribute("class", "caption");
			$parentNode->insertBefore($captionDiv, $this);

			// Create actual caption element inside the div
			$captionElement = $this->ownerDocument->createElement("caption");
			$captionDiv->appendChild($captionElement);
			
			/* Set table title
			* @var $tableTitle JATSText
			*/
			if (count($jatsTable->getTitle()) > 0) {
				$spanTitle = $this->ownerDocument->createElement("span");
				$spanTitle->setAttribute("class", "title");
				$captionElement->appendChild($spanTitle);
				foreach ($jatsTable->getTitle() as $tableTitle) {
					HTMLText::extractText($tableTitle, $spanTitle);
				}
				// Add spacing between caption and table
				$spacer = $this->ownerDocument->createElement("br");
				$parentNode->insertBefore($spacer, $this);
			}
		}

		// Converting table head
		$hasHead = false;
		$hasBody = false;

		$htmlHead = $this->ownerDocument->createElement("thead");
		$htmlBody = $this->ownerDocument->createElement("tbody");

		foreach ($jatsTable->getContent() as $row) {
			/* @var $row JATSRow */
			switch ($row->getType()) {
				case 1:
					$hasHead = true;
					$htmlRow = $this->ownerDocument->createElement("tr");
					$htmlHead->appendChild($htmlRow);
					foreach ($row->getContent() as $cell) {

						/* @var $cell JATSCell */
						$htmlCell = new Cell($cell->getType());
						$htmlRow->appendChild($htmlCell);
						$htmlCell->setContent($cell);

					}
					break;
				case 2:
					$hasBody = true;
					$this->extractRowsAndCells($htmlBody, $row);
					break;
				case 3:
					$this->extractRowsAndCells($this, $row);
					break;
			}
		}

		if ($hasHead) {
			$this->appendChild($htmlHead);
		}

		if ($hasBody) {
			$this->appendChild($htmlBody);
		}

		/* Create a separate div for notes after the table
		* @var $jatsTable JATSPar
		*/
		if (count($jatsTable->getNotes()) > 0 && $parentNode) {
			// Create notes container div
			$notesContainer = $this->ownerDocument->createElement("div");
			$notesContainer->setAttribute("class", "table-notes");
			$parentNode->insertBefore($notesContainer, $this->nextSibling);
			
			foreach ($jatsTable->getNotes() as $tableContent) {
				$par = new Par("span");
				$notesContainer->appendChild($par);
				$par->setAttribute("class", "notes");
				$par->setContent($tableContent);
			}
		}
	}

	/**
	 * @param $htmlHead \DOMElement
	 * @param $row JATSRow
	 */
	private function extractRowsAndCells(\DOMElement $htmlElement, JATSRow $row): void
	{
		$htmlRow = $this->ownerDocument->createElement("tr");
		$htmlElement->appendChild($htmlRow);
		foreach ($row->getContent() as $cell) {

			/* @var $cell JATSCell */
			$htmlCell = new Cell($cell->getType());
			$htmlRow->appendChild($htmlCell);
			$htmlCell->setContent($cell);

		}
	}
}