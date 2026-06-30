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
use JATSParser\Back\Dataset;
use JATSParser\Back\Software;
use JATSParser\Back\Patent;
use JATSParser\Back\Article;
use JATSParser\Back\Newspaper;

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

		$this->setContributors('getAuthors', 'author');
		$this->setContributors('getEditors', 'editor');
		$this->setContributors('getTranslators', 'translator');
		$this->setContributors('getCompilers', 'compiler');
		$this->setContributors('getCurators', 'curator');
		$this->setContributors('getGuestEditors', 'editor'); // En apa guest editors can fallback to editor o guest-editor
		$this->setContributors('getCoordinators', 'director'); // director is standard mapping for coordinator
		$this->setContributors('getIllustrators', 'illustrator');
		$this->setPublisherFromAssignees(); // Assignees are often institutions or publishers, mapping as string to avoid CiteProc crash
		$this->setContributors('getDirectors', 'director');


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
			if (array_key_exists('isbn', $ids)) {
				$this->content->{'ISBN'} = $ids['isbn'];
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
		$this->setSimpleProperty('edition', 'getEdition');
		$this->setDate('original-date', 'getComment');
		$this->setSimpleProperty('part-title', 'getPartTitle');

		switch (get_class($this->jatsReference)) {

			case "JATSParser\Back\Journal":

				/* @var $jatsReference Journal */
				$this->content->type = 'article-journal';
				$this->setSimpleProperty('number', 'getElocationId');
				break;

			case "JATSParser\Back\Book":

				/* @var $jatsReference Book */
				$this->content->type = 'book';
				break;

			case "JATSParser\Back\Chapter":
				/* @var $jatsReference Chapter */
				$this->content->type = 'chapter';
				
				// Fix: genre is erroneously set to publisher-loc by default
				unset($this->content->genre);

				// Fix: elocation-id mapped to URL causes numbers to appear as URLs
				if (isset($this->content->URL) && is_numeric($this->content->URL)) {
					unset($this->content->URL);
				}
				break;

			case "JATSParser\Back\Conference":

				/* @var $jatsReference Conference */
				$this->content->type = 'conference';
				$this->setDate('issued', 'getIssuedDate');
				$this->setSimpleProperty('genre', 'getComment');
				break;

			case "JATSParser\Back\Webpage":
				/* @var $jatsReference Webpage */
				$this->content->type = 'webpage';
				$this->setSimpleProperty('container-title', 'getContainerTitle');
				break;

			case "JATSParser\Back\Thesis":
				/* @var $jatsReference Thesis */
				$this->content->type = 'thesis';
				$this->setSimpleProperty('genre', 'getGenre');
				$this->setSimpleProperty('number', 'getPublicationNumber');
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

			case "JATSParser\Back\Software":
				/* @var $jatsReference Software */
				$this->content->type = 'software';
				$this->setSimpleProperty('version', 'getVersion');
				break;

			case "JATSParser\Back\Patent":
				/* @var $jatsReference Patent */
				$this->content->type = 'patent';
				$this->setDate('issued', 'getIssuedDate');
				$this->setSimpleProperty('authority', 'getAuthority');
				$this->setSimpleProperty('number', 'getNumber');
				break;

			case "JATSParser\Back\Article":
				/* @var $jatsReference Article */
				$this->content->type = 'article';
				break;

			case "JATSParser\Back\Newspaper":
				/* @var $jatsReference Newspaper */
				$this->content->type = 'article-newspaper';
				break;
		}
	}

	protected function setContributors(string $method, string $cslProperty): void {
		if (method_exists($this->jatsReference, $method) && !empty($this->jatsReference->$method())) {
			foreach ($this->jatsReference->$method() as $individual) {
				if (get_class($individual) == 'JATSParser\Back\Individual') { /** @var $individual Individual */
					$contributor = new \stdClass();
					$given = $individual->getGivenNames();
					$surname = $individual->getSurname();

					if (!empty($surname) && !empty($given)) {
						$contributor->family = $surname;
						$contributor->given = $given;
					} elseif (!empty($surname)) {
						$contributor->family = $surname;
					} elseif (!empty($given)) {
						$contributor->family = $given; // CiteProc-PHP exige que exista 'family' si es un nombre. Las instituciones caen aquí.
					}

					$this->content->{$cslProperty}[] = $contributor;

				} elseif (get_class($individual) == 'JATSParser\Back\Collaboration') { /* @var $individual \JATSParser\Back\Collaboration */
					$contributor = new \stdClass();
					$contributor->family = trim($individual->getName());
					$this->content->{$cslProperty}[] = $contributor;
				}
			}
		}
	}
	protected function setPublisherFromAssignees(): void {
		if (method_exists($this->jatsReference, 'getAssignees') && !empty($assignees = $this->jatsReference->getAssignees())) {
			$names = [];
			foreach ($assignees as $individual) {
				if (get_class($individual) == 'JATSParser\Back\Individual') { /** @var $individual Individual */
					$given = $individual->getGivenNames();
					$surname = $individual->getSurname();
					if (!empty($surname) && !empty($given)) {
						$names[] = $surname . ', ' . $given;
					} elseif (!empty($surname)) {
						$names[] = $surname;
					} elseif (!empty($given)) {
						$names[] = $given;
					}
				} elseif (get_class($individual) == 'JATSParser\Back\Collaboration') { /* @var $individual \JATSParser\Back\Collaboration */
					$names[] = trim($individual->getName());
				}
			}
			if (!empty($names)) {
				$this->content->publisher = implode('; ', $names);
			}
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
