<?php namespace JATSParser\Back;

use JATSParser\Back\AbstractReference as AbstractReference;

class Thesis extends AbstractReference {

	/* @var string */
	private $title;

	/* @var string */
	private $publisherLoc;

	/* @var string */
	private $publisherName;

	/* @var string */
	/* @var string */
	private $publicationNumber;

	public function __construct(\DOMElement $reference) {
		parent::__construct($reference);

		// Thesis title
		$this->title = $this->extractFromElement($reference, ".//article-title[1]");

		// Institución y ubicación
		// Logic:
		// 1. If <institution> (or institution-name) exists, it is the Awarding Institution -> maps to CSL 'publisher'.
		// 2. If <publisher-name> ALSO exists, it is the Database/Service -> maps to CSL 'archive'.
		// 3. If only <publisher-name> exists (no institution), it falls back to 'publisher'.

		$instName = $this->extractFromElement($reference, ".//institution-name[1]|.//institution[1]");
		$pubDesc = $this->extractFromElement($reference, ".//publisher-name[1]");

		if (!empty($instName)) {
			$this->publisherName = $instName;
			if (!empty($pubDesc)) {
				// Map publisher-name to archive for Published Thesis (Database)
				$this->pubIdType['archive'] = $pubDesc;
			}
		} else {
			// Fallback: No institution tag found, assume publisher-name is the institution
			$this->publisherName = $pubDesc;
		}
		
		// Prioritize institution-loc over publisher-loc
		$pubLoc = $this->extractFromElement($reference, ".//institution-loc[1]");
		$this->publisherLoc = !empty($pubLoc) ? $pubLoc : $this->extractFromElement($reference, ".//publisher-loc[1]");

		// Tipo/grado de la tesis (p.ej., "Tesis de maestría")
		// Prioritize degree over comment
		$degree = $this->extractFromElement($reference, ".//degree[1]");
		$this->genre = !empty($degree) ? $degree : $this->extractFromElement($reference, ".//comment[1]");

		// Publication number: prioritize explicit publication-number tag, then pub-id with type="other"
		$pubNum = $this->extractFromElement($reference, ".//publication-number[1]");
		$this->publicationNumber = !empty($pubNum) ? $pubNum : $this->extractFromElement($reference, ".//pub-id[@pub-id-type='other'][1]");
	}

	public function getId(): string { return $this->id; }
	public function getTitle(): string { return $this->title; }
	public function getAuthors(): array { return $this->authors; }
	public function getEditors(): array { return $this->editors; }
	public function getYear(): string { return $this->year; }
	public function getUrl(): string { return $this->url; }
	public function getPublisherLoc(): string { return $this->publisherLoc; }
	public function getPublisherName(): string { return $this->publisherName; }
	public function getGenre(): string { return $this->genre; }
	public function getPublicationNumber(): string { return $this->publicationNumber; }
	public function getPubIdType(): array { return $this->pubIdType; }
}
