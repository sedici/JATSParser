<?php namespace JATSParser\Back;

use JATSParser\Back\AbstractReference as AbstractReference;

class Webpage extends AbstractReference {

	/* @var $title string */
	private $title;

	/* @var $publisherLoc string */
	private $publisherLoc;

	/* @var $publisherName string */
	private $publisherName;

	/* @var $containerTitle string Website / container-title */
	private $source;

	/* @var $month string */
	private $month;

	/* @var $day string */
	private $day;

	/* @var $accessDate string */
	private $accessDate;

	public function __construct(\DOMElement $reference) {

		parent::__construct($reference);

		$this->title = $this->extractFromElement($reference, ".//article-title[1]");
		$this->publisherLoc = $this->extractFromElement($reference, ".//publisher-loc[1]");
		$this->publisherName = $this->extractFromElement($reference, ".//publisher-name[1]");

		// Container (container-title en metadata)
		$this->source = $this->extractFromElement($reference, ".//source[1]");

		// Fecha publicada (además del año ya manejado por AbstractReference)
		$this->month = $this->extractFromElement($reference, ".//month[1]");
		$this->day   = $this->extractFromElement($reference, ".//day[1]");

		// Fecha de acceso (si existe en el JATS)
		$this->accessDate = $this->extractFromElement($reference, './/date-in-citation[1]');

		// URL explícita del recurso (coincide con URL en metadata para webpages)
		$this->url = $this->extractFromElement($reference, ".//uri[1]");
	}

	/**
	 * @return string
	 */
	public function getId(): string
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getTitle(): string {
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
	public function getPublisherLoc(): string
	{
		return $this->publisherLoc;
	}

	/**
	 * @return string
	 */
	public function getPublisherName(): string
	{
		return $this->publisherName;
	}

	/**
	 * @return array
	 */
	public function getPubIdType(): array
	{
		return $this->pubIdType;
	}

	/**
	 * @return string
	 * @brief container-title del sitio (p.ej., "Sitio Ejemplo")
	 */
	public function getSource(): string
	{
		return $this->source;
	}

	/**
	 * @return string
	 * @brief mes de publicación si está disponible
	 */
	public function getMonth(): string
	{
		return $this->month;
	}

	/**
	 * @return string
	 * @brief día de publicación si está disponible
	 */
	public function getDay(): string
	{
		return $this->day;
	}

	/**
	 * @return string
	 * @brief fecha de acceso si está disponible (libre según venga en JATS)
	 */
	public function getAccessDate(): string
	{
		return $this->accessDate;
	}

	/**
	 * @return string
	 * @brief YYYY[-MM[-DD]] si existen los componentes
	 */
	public function getIssuedDate(): string
	{
		$parts = array_values(array_filter([$this->year, $this->month, $this->day], static function($v) {
			return $v !== null && $v !== '';
		}));
		return implode('-', $parts);
	}
}
