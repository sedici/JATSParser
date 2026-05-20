<?php namespace JATSParser\Back;

interface Reference {

	public function getId();

	public function getTitle();

	public function getAuthors();

	public function getEditors();

	public function getYear();

	public function getTranslators();

	public function getCompilers();

	public function getCurators();

	public function getGuestEditors();

	public function getCoordinators();

	public function getIllustrators();

	public function getInventors();

	public function getAssignees();

	public function getDirectors();

}