<?php namespace JATSParser\HTML;


use JATSParser\Back\AbstractReference;
use JATSParser\Back\Individual;
use JATSParser\Back\Journal;
use JATSParser\Back\Book;
use JATSParser\Back\Chapter;
use JATSParser\Back\Conference;
use JATSParser\Back\Webpage;
use JATSParser\Back\Thesis;
use JATSParser\Back\Magazine;
use JATSParser\Back\Dataset; // NUEVO

class Reference {

	/** @var $content \stdClass */
	private $content;
	private $jatsReference;

	public function __construct(AbstractReference $jatsReference) {
		$this->jatsReference = $jatsReference;
		$this->setContent();
	}

	public function setContent() {
		if (!isset($this->content)) $this->content = new \stdClass();

		$this->setSimpleProperty('id', 'getId');

		if (!empty($this->jatsReference->getAuthors())) {
			foreach ($this->jatsReference->getAuthors() as $individual) {
				if (get_class($individual) == 'JATSParser\Back\Individual') { /** @var $individual Individual */
					$author = new \stdClass();
					if (!empty($individual->getGivenNames())) {
						$author->family = $individual->getSurname();
					}

					if (!empty($individual->getSurname())) {
						$author->given = $individual->getGivenNames();
					}

					$this->content->author[] = $author;

				}
			}
		}

		if (!empty($this->jatsReference->getEditors())) {
			foreach ($this->jatsReference->getEditors() as $individual) {
				if (get_class($individual) == 'JATSParser\Back\Individual') { /** @var $individual Individual */
					$editor = new \stdClass();
					if (!empty($individual->getGivenNames())) {
						$editor->family = $individual->getSurname();
					}

					if (!empty($individual->getSurname())) {
						$editor->given = $individual->getGivenNames();
					}

					$this->content->editor[] = $editor;
				}
			}
		}

		$this->setSimpleProperty('URL', 'getUrl');
		$this->setSimpleProperty('title', 'getTitle');

		// specific properties
		if (checkdate(1, 1, (int) $this->jatsReference->getYear())) {
			$this->setDate('issued', 'getYear');
		}
		$this->setDate('issued', 'getIssuedDate');
		$this->setDate('accessed', 'getAccessDate');

		$this->setSimpleProperty('container-title', 'getJournal');
		$this->setSimpleProperty('journal', 'getJournal');
		$this->setSimpleProperty('volume', 'getVolume');
		$this->setSimpleProperty('issue', 'getIssue');
		$this->setSimpleProperty('page-first', 'getFpage');
		$this->setSimpleProperty('page', 'getPages');

		if (method_exists($this->jatsReference, 'getPubIdType') && array_key_exists('doi', $this->jatsReference->getPubIdType())) {
			$doi = $this->jatsReference->getPubIdType()['doi'];
			// Can't pass URL, see https://github.com/Vitaliy-1/JATSParserPlugin/issues/63
			if (self::isDoiUrl($doi)) {
				$doi = substr_replace($doi, '', 0, strlen(DOI_REFERENCE_PREFIX));
			}
			$this->content->{'DOI'} =$doi;
		}
		// NUEVO: dataset ids comunes
		if (method_exists($this->jatsReference, 'getPubIdType')) {
			$ids = $this->jatsReference->getPubIdType();
			if (array_key_exists('archive', $ids)) {
				$this->content->{'archive'} = $ids['archive'];
			}
			if (array_key_exists('accession', $ids)) {
				$this->content->{'archive_location'} = $ids['accession'];
			}
		}

		$this->setSimpleProperty('publisher', 'getPublisherName');
		$this->setSimpleProperty('publisher-place', 'getPublisherLoc');
		$this->setSimpleProperty('container-title', 'getBook');
		$this->setSimpleProperty('container-title', 'getSource');
		$this->setSimpleProperty('event', 'getConfName');
		$this->setDate('event-date', 'getConfDate');
		$this->setSimpleProperty('event-place', 'getConfLoc');
		$this->setSimpleProperty('genre', 'getPublisherLoc'); 


		switch (get_class($this->jatsReference)) {

			case "JATSParser\Back\Journal":

				/* @var $jatsReference Journal */
				$this->content->type = 'article-journal';
				break;

			case "JATSParser\Back\Book":

				/* @var $jatsReference Book */
				$this->content->type = 'book';
				break;

			case "JATSParser\Back\Chapter":

				/* @var $jatsReference Chapter */
				$this->content->type = 'chapter';
				break;

			case "JATSParser\Back\Conference":

				/* @var $jatsReference Conference */
				$this->content->type = 'conference';
				break;

			case "JATSParser\Back\Webpage":
				/* @var $jatsReference Webpage */
				$this->content->type = 'webpage';
				$this->setSimpleProperty('container-title', 'getContainerTitle');
				$this->setDate('issued', 'getIssuedDate');
				$this->setDate('accessed', 'getAccessDate');
				break;

			case "JATSParser\Back\Thesis":
				/* @var $jatsReference Thesis */
				$this->content->type = 'thesis';
				$this->setSimpleProperty('genre', 'getGenre');
				break;

			case "JATSParser\Back\Magazine":
				/* @var $jatsReference Magazine */
				$this->content->type = 'article-magazine';
				$this->setDate('issued', 'getIssuedDate');
				break;

			case "JATSParser\Back\Dataset": 
				/* @var $jatsReference Dataset */
				$this->content->type = 'dataset';
				$this->setDate('issued', 'getIssuedDate');
				break;
		}
	}

	/**
	 * @return array
	 */
	public function getContent(): \stdClass
	{
		return $this->content;
	}

	/**
	 * @param $property string JSON property
	 * @param $method string method to retrieve property from JATS Parser Reference
	 * @return void
	 */
	protected function setSimpleProperty(string $property, string $method): void {
		if (method_exists($this->jatsReference, $method) && !empty($this->jatsReference->$method())) {
			$this->content->{$property} = $this->jatsReference->$method();
		}
	}

	protected function setDate(string $property, string $method): void {
		if (method_exists($this->jatsReference, $method) && !empty($value = $this->jatsReference->$method())) {
			$dateParts = [];
			if (is_int($value) || (is_string($value) && ctype_digit($value))) {
				$dateParts = [(int)$value];
			} elseif (is_string($value)) {
				$val = trim($value);
				if (preg_match('/^\d{4}(?:-\d{1,2}){0,2}$/', $val)) {
					$dateParts = array_map('intval', explode('-', $val));
				}
			}
			if (!empty($dateParts)) {
				$date = new \stdClass();
				$date->{'date-parts'} = [ $dateParts ];
				$this->content->{$property} = $date;
			}
		}
	}

	/**
	 * @return bool
	 * @brief checks if generated CJSON-CSL doesn't contain ref specific info, e.g., title, authors, year.
	 * TODO find a better way of CSL validation
	 */
	public function refIsEmpty(): bool {
		$csl = (array) $this->content;
		// ID and type are assigned irrespectively to the reference content
		unset($csl['id']);
		unset($csl['type']);
		return empty($csl);
	}

	public function getJatsReference(): AbstractReference {
		return $this->jatsReference;
	}

	public static function isDoiUrl($doi) {
		return substr($doi, 0, strlen(DOI_REFERENCE_PREFIX)) === DOI_REFERENCE_PREFIX;
	}
}
