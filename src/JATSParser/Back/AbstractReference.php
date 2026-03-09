<?php namespace JATSParser\Back;

use docx2jats\jats\Element;
use JATSParser\Back\Reference as Reference;
use JATSParser\Back\Collaboration as Collaboration;
use JATSParser\Body\Document as Document;

Define('DOI_REFERENCE_PREFIX', 'https://doi.org/');
Define('PMID_REFERENCE_PREFIX', 'https://www.ncbi.nlm.nih.gov/pubmed/');
Define('PMCID_REFERENCE_PREFIX', 'https://www.ncbi.nlm.nih.gov/pmc/articles/');
abstract class AbstractReference implements Reference
{

	protected $xpath;

	/* @var $id string */
	protected $id;

	/* @var array can contain instances of Individual and Collaboration class */
	protected $authors;
	protected $editors;
	protected $translators;
	protected $compilers;
	protected $curators;
	protected $guestEditors;
	protected $coordinators;
	protected $illustrators;
	protected $inventors;
	protected $assignees;
	protected $directors;

	/* @var $year string */
	protected $year;

	/* @var $url string */
	protected $url;

	/* @var $pubIdType array publication Identifier for a cited publication */
	protected $pubIdType;

	protected $rawReference = '';

	protected $isMixed = false;

	abstract public function getId();

	abstract public function getTitle();

	abstract public function getAuthors();

	abstract public function getEditors();

	abstract public function getYear();

	abstract public function getUrl();

	abstract public function getPubIdType();

	protected function __construct(\DOMElement $reference)
	{
		$this->xpath = Document::getXpath();
		$this->authors = $this->extractContributors($reference, ['author', 'inventor'], true);
		$this->editors = $this->extractContributors($reference, ['editor']);
		$this->translators = $this->extractContributors($reference, ['translator']);
		$this->compilers = $this->extractContributors($reference, ['compiler']);
		$this->curators = $this->extractContributors($reference, ['curator']);
		$this->guestEditors = $this->extractContributors($reference, ['guest-editor']);
		$this->coordinators = $this->extractContributors($reference, ['coordinator']);
		$this->illustrators = $this->extractContributors($reference, ['illustrator']);
		$this->inventors = $this->extractContributors($reference, ['inventor']);
		$this->assignees = $this->extractContributors($reference, ['assignee']);
		$this->directors = $this->extractContributors($reference, ['director']);
		$this->id = $this->extractId($reference);
		$this->year = $this->extractFromElement($reference, './/year[1]');

		$this->url = $this->extractFromElement($reference, './/ext-link[@ext-link-type="uri"][1]|.//elocation-id[1]|.//uri[1]');
		$this->pubIdType = $this->extractPubIdType($reference);

		$citNode = $this->getFirstChildElement($reference);
		if ($citNode) {
			if ($citNode->tagName === 'mixed-citation') $this->isMixed = true;
			$this->rawReference = $citNode->nodeValue;
		}
	}

	protected function extractFromElement(\DOMElement $reference, string $xpathExpression)
	{
		$property = '';
		$searchNodes = $this->xpath->query($xpathExpression, $reference);
		if ($searchNodes->length > 0) {
			foreach ($searchNodes as $searchNode) {
				$property = htmlspecialchars(trim($searchNode->nodeValue));
			}
		}
		return $property;
	}

	private function extractId(\DOMElement $reference)
	{
		$id = '';
		if ($reference->hasAttribute("id")) {
			$id = $reference->getAttribute("id");
		}
		return $id;
	}

	protected function extractContributors(\DOMElement $reference, array $types, bool $allowNoPersonGroup = false)
	{
		$contributors = array();

		$nameNodes = $this->xpath->query(".//name|.//collab", $reference);
		if ($nameNodes->length > 0) {
			/* @var $nameNode \DOMElement */
			foreach ($nameNodes as $nameNode) {
				$parentOfName = $nameNode->parentNode;
				
				$typeMatches = false;
				if ($parentOfName->nodeName !== 'person-group') {
					$typeMatches = $allowNoPersonGroup;
				} else {
					$typeMatches = in_array($parentOfName->getAttribute('person-group-type'), $types, true);
				}

				if ($nameNode->nodeName === 'name' && $typeMatches) {
					$individual = new Individual($nameNode);
					$contributors[] = $individual;
				} elseif ($nameNode->nodeName === 'collab' && $typeMatches) {
					$collaborator = new Collaboration($nameNode);
					$contributors[] = $collaborator;
				}
			}
		}
		return $contributors;
	}

	public function getTranslators(): array {
		return $this->translators ?? [];
	}

	public function getCompilers(): array {
		return $this->compilers ?? [];
	}

	public function getCurators(): array {
		return $this->curators ?? [];
	}

	public function getGuestEditors(): array {
		return $this->guestEditors ?? [];
	}

	public function getCoordinators(): array {
		return $this->coordinators ?? [];
	}

	public function getIllustrators(): array {
		return $this->illustrators ?? [];
	}

	public function getInventors(): array {
		return $this->inventors ?? [];
	}

	public function getAssignees(): array {
		return $this->assignees ?? [];
	}

	public function getDirectors(): array {
		return $this->directors ?? [];
	}


	/**
	 * @return array
	 * Key => Publication ID Typy (DOI, PMID, PMCID), Value => Valid URL
	 */

	private function extractPubIdType(\DOMElement $reference): array
	{
		$pubIdType = array();

		$pubIdNodes = $this->xpath->query('.//pub-id', $reference);
		if ($pubIdNodes->length > 0) {
			/* @var $pubIdNode \DOMElement */
			foreach ($pubIdNodes as $pubIdNode) {
				if ($pubIdNode->getAttribute('pub-id-type')) {
					/* Ideally, we should retrieve Pub ID Type as a key  and URL here as an array value */
					$pubIdKey = $pubIdNode->getAttribute('pub-id-type');
					$pubIdValue = $pubIdNode->nodeValue;

					switch (trim($pubIdKey)) {
						/* TODO It's quite probably that we will need additional checks here */
						case "doi":
							filter_var($pubIdValue, FILTER_VALIDATE_URL) ? $pubIdType[$pubIdKey] = $pubIdValue : $pubIdType[$pubIdKey] = DOI_REFERENCE_PREFIX . trim($pubIdValue);
							break;
						case "pmid":
							filter_var($pubIdValue, FILTER_VALIDATE_URL) ? $pubIdType[$pubIdKey] = $pubIdValue : $pubIdType[$pubIdKey] = PMID_REFERENCE_PREFIX . trim($pubIdValue);
							break;
						case "pmcid":
							filter_var($pubIdValue, FILTER_VALIDATE_URL) ? $pubIdType[$pubIdKey] = $pubIdValue : $pubIdType[$pubIdKey] = PMCID_REFERENCE_PREFIX . trim($pubIdValue);
							break;
						case "accession":
						case "ark":
						case "archive":
						case "isbn":
							$pubIdType[$pubIdKey] = trim($pubIdValue);
							break;
					}
				}
			}
		}
		return $pubIdType;
	}

	/**
	 * @return bool
	 * @brief check if it's mixed citation (may have untagged text)
	 */
	public function isMixed(): bool {
		return $this->isMixed;
	}

	/**
	 * @return string
	 * @brief contains only the text/nodeValue of the reference node
	 */
	public function getRawReference(): string {
		return $this->rawReference;
	}

	/**
	 * @param \DOMElement $el
	 * @return \DOMElement|null
	 * @brief return the first child element that is a DOMElement, e.g., to avoid DOMText children
	 */
	protected function getFirstChildElement(\DOMElement $el): ?\DOMElement {
		foreach ($el->childNodes as $refChild) {
			if ($refChild->nodeType === XML_ELEMENT_NODE) {
				return $refChild;
			}
		}
		return null;
	}
}
