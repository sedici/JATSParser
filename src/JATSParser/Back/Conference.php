<?php namespace JATSParser\Back;


use JATSParser\Back\AbstractReference as AbstractReference;

class Conference extends AbstractReference {

	/* @var $title string */
	private $title;

	/* @var $confName string */
	private $confName;

	/* @var $confLoc string */
	private $confLoc;

	/* @var $confDate string */
	private $confDate;

	/* @var $source string */
	private $source;

	public function __construct(\DOMElement $reference) {

		parent::__construct($reference);

		$this->title = $this->extractFromElement($reference, ".//article-title");
		$this->source = $this->extractFromElement($reference, ".//source");
		$this->confName = $this->extractFromElement($reference, ".//conf-name");
		$this->confLoc = $this->extractFromElement($reference, ".//conf-loc");
		$this->confDate = $this->extractFromElement($reference, ".//conf-date");
		$this->url = $this->extractFromElement($reference, './/elocation-id[1]|.//ext-link[@ext-link-type="uri"][1]|.//uri[1]');
	}

	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getTitle(): string
	{
		return $this->title;
	}

	/**
	 * @return array
	 */
	public function getAuthors(): array
	{
		return $this->authors;
	}


	/**
	 * @return array
	 */
	public function getEditors(): array
	{
		return $this->editors;
	}

	/**
	 * @return string
	 */
	public function getYear(): string
	{
		return $this->year;
	}

	/**
	 * @return string
	 */
	public function getUrl(): string
	{
		return $this->url;
	}

	/**
	 * @return string
	 */
	public function getConfDate(): string
	{
		return $this->confDate;
	}

	/**
	 * @return string
	 */
	public function getConfLoc(): string
	{
		return $this->confLoc;
	}

	/**
	 * @return string
	 */
	public function getConfName(): string
	{
		return $this->confName;
	}

	/**
	 * @return string
	 */
	public function getSource(): string
	{
		return $this->source;
	}

	/**
	 * @return array
	 */
	public function getPubIdType(): array
	{
		return $this->pubIdType;
	}
}

