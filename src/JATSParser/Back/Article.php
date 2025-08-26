<?php namespace JATSParser\Back;

use JATSParser\Back\AbstractReference as AbstractReference;

class Article extends AbstractReference
{
	private $title = '';
	private $containerTitle = '';
	private $month = '';
	private $day = '';

	public function __construct(\DOMElement $reference)
	{
		parent::__construct($reference);
		$this->title = $this->extractFromElement($reference, ".//article-title[1]");
		$this->containerTitle = $this->extractFromElement($reference, ".//source[1]");
		$this->month = $this->extractFromElement($reference, ".//month[1]");
		$this->day = $this->extractFromElement($reference, ".//day[1]");
        
    }

	public function getId(): string { return $this->id; }
	public function getTitle(): string { return $this->title; }
	public function getAuthors(): array { return $this->authors; }
	public function getEditors(): array { return $this->editors; }
	public function getYear(): string { return $this->year; }
	public function getUrl(): string { return $this->url; }
	public function getPubIdType(): array { return $this->pubIdType; }

	// Para mapeo del contenedor
	public function getJournal(): string { return $this->containerTitle; }
	public function getSource(): string { return $this->containerTitle; }

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
