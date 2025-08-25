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
	private $genre;

	public function __construct(\DOMElement $reference) {
		parent::__construct($reference);

		// Thesis title
		$this->title = $this->extractFromElement($reference, ".//article-title[1]");

		// Institución y ubicación (si no hay publisher-name, usamos source como fallback)
		$this->publisherName = $this->extractFromElement($reference, ".//publisher-name[1]");
		
		$this->publisherLoc  = $this->extractFromElement($reference, ".//publisher-loc[1]");

		// Tipo/grado de la tesis (p.ej., "Tesis de maestría"); se usa comment como fuente común
		$this->genre = $this->extractFromElement($reference, ".//comment[1]");
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
	public function getPubIdType(): array { return $this->pubIdType; }
}
