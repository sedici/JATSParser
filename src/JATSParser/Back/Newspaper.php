<?php namespace JATSParser\Back;

use JATSParser\Back\AbstractReference as AbstractReference;

class Newspaper extends AbstractReference
{
	private $title = '';
	private $containerTitle = '';
	private $month = '';
	private $day = '';
	private $fpage = '';
	private $lpage = '';
	private $pageRange = '';
	private $volume = '';
	private $edition = '';
	private $partTitle = '';
	private $issue = '';

	public function __construct(\DOMElement $reference)
	{
		parent::__construct($reference);

		$this->title          = $this->extractFromElement($reference, ".//article-title[1]");
		$this->containerTitle = $this->extractFromElement($reference, ".//source[1]");
		$this->month          = $this->extractFromElement($reference, ".//month[1]");
		$this->day            = $this->extractFromElement($reference, ".//day[1]");
		$this->fpage          = $this->extractFromElement($reference, ".//fpage[1]");
		$this->lpage          = $this->extractFromElement($reference, ".//lpage[1]");
		$this->pageRange      = $this->extractFromElement($reference, ".//page-range[1]");
		$this->volume         = $this->extractFromElement($reference, ".//volume[1]");
		$this->edition        = $this->extractFromElement($reference, ".//edition[1]");
		$this->partTitle      = $this->extractFromElement($reference, ".//part-title[1]");
		$this->issue          = $this->extractFromElement($reference, ".//issue[1]");
	}

	public function getId(): string { return $this->id; }
	public function getTitle(): string { return $this->title; }
	public function getAuthors(): array { return $this->authors; }
	public function getEditors(): array { return $this->editors; }
	public function getYear(): string { return $this->year; }
	public function getUrl(): string { return $this->url; }
	public function getPubIdType(): array { return $this->pubIdType; }

	public function getJournal(): string { return $this->containerTitle; }
	public function getSource(): string { return $this->containerTitle; }

	public function getMonth(): string { return $this->month; }
	public function getDay(): string { return $this->day; }

	public function getFpage(): string { return $this->fpage; }
	public function getPages(): string {
		if (!empty($this->pageRange)) return $this->pageRange;
		if (!empty($this->fpage) && !empty($this->lpage)) return $this->fpage . '-' . $this->lpage;
		return $this->fpage ?: '';
	}

	public function getVolume(): string { return $this->volume; }
	public function getIssue(): string { return $this->issue; }
	public function getEdition(): string { return $this->edition; }
	public function getPartTitle(): string { return $this->partTitle; }

	// YYYY[-MM[-DD]]
	public function getIssuedDate(): string
	{
		$parts = array_values(array_filter([$this->year, $this->month, $this->day], static function($v) {
			return $v !== null && $v !== '';
		}));
		return implode('-', $parts);
	}
}
