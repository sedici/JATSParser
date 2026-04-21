<?php namespace JATSParser\Back;

use JATSParser\Back\AbstractReference as AbstractReference;

class Software extends AbstractReference
{
	/* @var string */
	private $title;

	/* @var string */
	private $version;

	/* @var string */
	private $month;

	/* @var string */
	private $day;

    /* @var string */
    private $publisherName;

    /* @var string */
    private $publisherLoc;

	public function __construct(\DOMElement $reference)
	{
		parent::__construct($reference);

		$this->title   = $this->extractFromElement($reference, ".//source[1]");
		$this->version = $this->extractFromElement($reference, ".//version[1]");

		$this->month = $this->extractFromElement($reference, ".//month[1]");
		$this->day   = $this->extractFromElement($reference, ".//day[1]");

        $this->publisherName = $this->extractFromElement($reference, ".//publisher-name[1]");
        $this->publisherLoc  = $this->extractFromElement($reference, ".//publisher-loc[1]");

		$this->url = $this->extractFromElement($reference, ".//ext-link[@ext-link-type=\"uri\"][1]|.//uri[1]");
	}

	public function getId(): string { return $this->id; }
	public function getTitle(): string { return $this->title; }
	public function getVersion(): string { return $this->version; }
	public function getAuthors(): array { return $this->authors; }
	public function getEditors(): array { return $this->editors; }
	public function getYear(): string { return $this->year; }
	public function getUrl(): string { return $this->url; }
	public function getPubIdType(): array { return $this->pubIdType; }
    public function getPublisherName(): string { return $this->publisherName; }
    public function getPublisherLoc(): string { return $this->publisherLoc; }

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
