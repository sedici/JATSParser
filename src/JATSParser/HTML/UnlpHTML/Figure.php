<?php namespace JATSParser\HTML\UnlpHTML;

use JATSParser\Body\Figure as JATSFigure;
use JATSParser\Body\Par as JATSPar;
use JATSParser\Body\Text as JATSText;
use JATSParser\HTML\Par as Par;
use JATSParser\HTML\Text as HTMLText;

class Figure extends \DOMElement {
	public function __construct() {

		parent::__construct("figure");

	}

	public function setContent(JATSFigure $jatsFigure) {

        // Set figure id. Needed for links from referenceces to the figure
        $this->setAttribute("id", $jatsFigure->getId());

        // Create title paragraph first (to be displayed above the image)
        $titleNode = $this->ownerDocument->createElement("div");
        $titleNode->setAttribute("class", "caption-title");
        $this->appendChild($titleNode);

        // Set figure label (e.g., Figure 1)
        if ($jatsFigure->getLabel()) {
            $spanLabel = $this->ownerDocument->createElement("span");
            $spanLabel->setAttribute("class", "label");
            $titleNode->appendChild($spanLabel);
            $textNode = $this->ownerDocument->createTextNode(HTMLText::checkPunctuation($jatsFigure->getLabel()));
            $spanLabel->appendChild($textNode);
        }

        /* Set figure title
        * @var $figureTitle JATSText
        */
        if (count($jatsFigure->getTitle()) > 0) {
            $spanTitle = $this->ownerDocument->createElement("span");
            $spanTitle->setAttribute("class", "title");
            $titleNode->appendChild($spanTitle);
            foreach ($jatsFigure->getTitle() as $figureTitle) {
                HTMLText::extractText($figureTitle, $spanTitle);
            }
        }

        // Add image wrapped inside div (after the title, before the notes)
        $divNode = $this->ownerDocument->createElement("div");
        $divNode->setAttribute("class", "figure");
        $this->appendChild($divNode);

        $srcNode = $this->ownerDocument->createElement("img");
        $divNode->appendChild($srcNode);
        $srcNode->setAttribute("src", rawurlencode($jatsFigure->getLink()));

        /* Set figure notes in separate paragraph AFTER the image
        * @var $figureContent JATSPar
        */
        if (count($jatsFigure->getContent()) > 0) {
 
            $notesNode = $this->ownerDocument->createElement("figcaption");
            $notesNode->setAttribute("class", "caption-notes");
  
            $this->appendChild($notesNode);
            
            foreach ($jatsFigure->getContent() as $figureContent) {
                $par = new Par("span");
                $notesNode->appendChild($par);
                $par->setAttribute("class", "notes");
                $par->setAttribute("style", "font-size: 0.85em;");
                $par->setContent($figureContent);
            }
        }
    }

}
