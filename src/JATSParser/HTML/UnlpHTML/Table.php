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
		
		// Set table title inside the table element (as first child)
		if (count($jatsTable->getTitle()) > 0) {
			$captionElement = $this->ownerDocument->createElement("caption");
			$this->appendChild($captionElement);
			
			$spanTitle = $this->ownerDocument->createElement("span");
			$spanTitle->setAttribute("class", "title");
			$captionElement->appendChild($spanTitle);
			foreach ($jatsTable->getTitle() as $tableTitle) {
				HTMLText::extractText($tableTitle, $spanTitle);
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