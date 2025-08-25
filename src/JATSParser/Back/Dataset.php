<?php namespace JATSParser\Back;

use JATSParser\Back\AbstractReference as AbstractReference;

class Dataset extends AbstractReference
{
	/* @var string */
	private $title;

	/* @var string */
	private $containerTitle;

	/* @var string */
	private $month;

	/* @var string */
	private $day;

	public function __construct(\DOMElement $reference)
	{
		parent::__construct($reference);

		// Título específico de datasets en JATS
		$this->title = $this->extractFromElement($reference, ".//data-title[1]");
		// Contenedor/fuente
		$this->containerTitle = $this->extractFromElement($reference, ".//source[1]");

		// Componentes de fecha adicionales
		$this->month  = $this->extractFromElement($reference, ".//month[1]");
		$this->day    = $this->extractFromElement($reference, ".//day[1]");

		// URL si existiera (no está en tu XML de ejemplo, pero contemplamos ambos tags)
		$this->url = $this->extractFromElement($reference, ".//ext-link[@ext-link-type=\"uri\"][1]|.//uri[1]");
	}

	public function getId(): string { return $this->id; }
	public function getTitle(): string { return $this->title; }
	public function getAuthors(): array { return $this->authors; }
	public function getEditors(): array { return $this->editors; }
	public function getYear(): string { return $this->year; }
	public function getUrl(): string { return $this->url; }
	public function getPubIdType(): array { return $this->pubIdType; }

	// Para mapeo genérico
	public function getJournal(): string { return $this->containerTitle; }

	// Componentes de fecha
	public function getMonth(): string { return $this->month; }
	public function getDay(): string { return $this->day; }

	// YYYY[-MM[-DD]]
	public function getIssuedDate(): string
	{
		$parts = array_values(array_filter([$this->year, $this->month, $this->day], static function($v) {
			return $v !== null && $v !== '';
		}));
		return implode('-', $parts);
	}
}
