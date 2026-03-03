<?php namespace JATSParser\Back;

use JATSParser\Back\AbstractReference as AbstractReference;

class Patent extends AbstractReference
{
	/* @var string */
	private $title;
	private $source;
	private $containerTitle;
	private $assignee;
	private $authority;
	private $number;
	private $month;
	private $day;

	public function __construct(\DOMElement $reference)
	{
		parent::__construct($reference);

		$articleTitle = $this->extractFromElement($reference, ".//article-title[1]");
		$this->source = $this->extractFromElement($reference, ".//source[1]");
		$this->title = $articleTitle ?: $this->source;
		$this->containerTitle = $this->source;

		// assignee
		$this->assignee = $this->extractFromElement(
			$reference,
			".//collab[@collab-type='assignee']//named-content[@content-type='name'][1]|.//collab[@collab-type='assignee'][1]"
		);

		// authority + number
		$this->authority = $this->extractFromElement($reference, ".//patent[1]/@country");
		
		$reportNum = $this->extractFromElement($reference, ".//pub-id[@pub-id-type='custom' and @custom-type='report-number'][1]");
		$patentNum = $this->extractFromElement($reference, ".//patent[1]");
		$this->number = $reportNum ?: $patentNum;

		// date parts
		$this->month = $this->extractFromElement($reference, ".//month[1]");
		$this->day   = $this->extractFromElement($reference, ".//day[1]");
	}

	public function getId(): string { return $this->id; }
	public function getTitle(): string { return $this->title; }
	public function getSource(): string { return $this->source; }
	public function getJournal(): string { return $this->containerTitle; }
	public function getAuthors(): array { return $this->authors; }
	public function getEditors(): array { return $this->editors; }
	public function getYear(): string { return $this->year; }
	public function getUrl(): string { return $this->url; }
	public function getPubIdType(): array { return $this->pubIdType; }

	public function getAuthority(): string { return $this->authority; }
	public function getNumber(): string { return $this->number; }

	// Map assignee via publisher getters expected by Reference
	public function getPublisherName(): string { return (string)$this->assignee; }
	public function getPublisherLoc(): string { return ''; }

	public function getMonth(): string { return $this->month; }
	public function getDay(): string { return $this->day; }

	// Build YYYY[-MM[-DD]] ensuring valid ranges for month/day
	public function getIssuedDate(): string
	{
		$y = $this->year;
		$m = $this->sanitizeIntRange($this->month, 1, 12);
		$d = $this->sanitizeIntRange($this->day, 1, 31);

		$parts = array_values(array_filter([$y, $m, $d], static function($v) {
			return $v !== null && $v !== '' && $v !== 0;
		}));
		return implode('-', $parts);
	}

	private function sanitizeIntRange($val, int $min, int $max): ?int
	{
		if ($val === null || $val === '') return null;
		$int = (int) preg_replace('/\D+/', '', (string)$val);
		if ($int < $min || $int > $max) return null;
		return $int;
	}
}
