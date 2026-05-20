<?php namespace JATSParser\Back;

use JATSParser\Back\AbstractReference as AbstractReference;

class Magazine extends AbstractReference
{
	/* @var string */
	private $title;

	/* @var string */
	private $journal;

	/* @var string */
	private $volume;

	/* @var string */
	private $issue;

	/* @var string */
	private $fpage;

	/* @var string */
	private $lpage;

	/* @var string pages range exacto si existe en el XML */
	private $pages;

	/* @var string */
	private $month;

	/* @var string */
	private $day;

	public function __construct(\DOMElement $reference)
	{
		parent::__construct($reference);

		$this->title  = $this->extractFromElement($reference, ".//article-title[1]");
		$this->journal = $this->extractFromElement($reference, ".//source[1]");
		$this->volume = $this->extractFromElement($reference, ".//volume[1]");
		$this->issue  = $this->extractFromElement($reference, ".//issue[1]");
		$this->fpage  = $this->extractFromElement($reference, ".//fpage[1]");
		$this->lpage  = $this->extractFromElement($reference, ".//lpage[1]");

		$this->month  = $this->extractFromElement($reference, ".//month[1]");
		$this->day    = $this->extractFromElement($reference, ".//day[1]");

		$this->pages = $this->extractFromElement($reference, ".//page-range[1]");

		$this->url = $this->extractFromElement($reference, ".//ext-link[@ext-link-type=\"uri\"][1]|.//uri[1]");
	}

	public function getId(): string { return $this->id; }
	public function getTitle(): string { return $this->title; }
	public function getAuthors(): array { return $this->authors; }
	public function getEditors(): array { return $this->editors; }
	public function getYear(): string { return $this->year; }
	public function getUrl(): string { return $this->url; }
	public function getPubIdType(): array { return $this->pubIdType; }

	public function getJournal(): string { return $this->journal; }
	public function getVolume(): string { return $this->volume; }
	public function getIssue(): string { return $this->issue; }
	public function getFpage(): string { return $this->fpage; }
	public function getLpage(): string { return $this->lpage; }

	public function getMonth(): string { return $this->month; }
	public function getDay(): string { return $this->day; }

	public function getIssuedDate(): string
	{
		$parts = array_values(array_filter([$this->year, $this->month, $this->day], static function($v) {
			return $v !== null && $v !== '';
		}));
		return implode('-', $parts);
	}

   	public function getPages(): string
	{
		$pages = '';

		if (!empty($this->getFpage()) && !empty($this->getLpage())) $pages = $this->getFpage() . '-' . $this->getLpage();

		return $pages;
	} 
}
