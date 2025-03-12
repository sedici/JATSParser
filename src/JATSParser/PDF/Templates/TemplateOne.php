<?php namespace JATSParser\PDF\Templates;

require_once(__DIR__ .'/../../../../vendor/tecnickcom/tcpdf/tcpdf.php');

use JATSParser\Body\Document as JATSDocument;
use JATSParser\HTML\Document as HTMLDocument;
use JATSParser\PDF\PDFBodyHelper;

require_once __DIR__ . '/../PDFConfig/Configuration.php';
require_once __DIR__ . '/../PDFConfig/Translations.php'; 


class TemplateOne extends \TCPDF {

	private $config;

	function __construct($config) {

		//save array with configurations and install fonts setted in Configuration.php.
		$this->config = $config;

		$this->SetCreator(PDF_CREATOR);
		$this->SetAuthor($config->getContributors());
		$this->SetSubject($config->getSubject());
		// setting up PDF
		parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$this->setTitle($config->getFullTitle());
		$this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$this->SetHeaderMargin(PDF_MARGIN_HEADER);
		$this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		$this->setImageScale(PDF_IMAGE_SCALE_RATIO);
		$this->SetFooterMargin(PDF_MARGIN_FOOTER);
		$this->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));		
		
		$this->setPrintHeader(false);
		$this->AddPage();
		$this->setPrintHeader(true);
		$this->TemplateBody();

		// Text (goes from JATSParser)
		$this->setCellPaddings(0, 0, 0, 0);
		$this->Body();
	}

	public function Header() {
		if ($this->header_xobjid === false) {
			//GET HEADER CONFIGURATION
			$headerConfig = $this->config->getHeaderConfig();
						
			// start a new XObject Template
			$this->header_xobjid = $this->startTemplate($this->w, $this->tMargin);

			$this->y = 1;

			$headerSize = 60;

			$journalData = $headerConfig['metadata']['journal_title'] . ', ' . $headerConfig['metadata']['journal_data'];
			$doiUrl = 'https://doi.org/' . $headerConfig['metadata']['doi'];
			$journalAffiliation = $headerConfig['metadata']['journal_affiliation'];
			$this->createNoClickableText($journalData, 10, 10, $headerConfig['config']['header_data']['text_color'], $headerConfig['config']['header_data']['font'], 'C');
			$this->createClickableText($doiUrl, $doiUrl, 10, $this->GetY(), $headerConfig['config']['doi']['text_color'], $headerConfig['config']['doi']['font'], 'C');
			$this->createNoClickableText($journalAffiliation, 10, $this->GetY(), $headerConfig['config']['journal_affiliation']['text_color'], $headerConfig['config']['journal_affiliation']['font'], 'C');
			$this->SetY((5.835 / $this->k) + max($headerSize, $this->y));
	
			$this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 0, '', 0, 0, 'C');
			$this->endTemplate();
		}

		// print header template
		$x = 0;
		$dx = 0;
		if (!$this->header_xobj_autoreset AND $this->booklet AND (($this->page % 2) == 0)) {
			// adjust margins for booklet mode
			$dx = ($this->original_lMargin - $this->original_rMargin);
		}
		if ($this->rtl) {
			$x = $this->w + $dx;
		} else {
			$x = 0 + $dx;
		}
		$this->printTemplate($this->header_xobjid, $x, 0, 0, 0, '', '', false);
		if ($this->header_xobj_autoreset) {
			// reset header xobject template at each page
			$this->header_xobjid = false;
		}
	}


		
	public function Footer() {
		//GET FOOTER CONFIGURATION
		$footerConfig = $this->config->getFooterConfig();
		$translationsConfig = $this->config->getTranslationsConfig();
		$localeKey = $this->config->getLocaleKeyConfig();
		$licenseUrl = $this->config->getLicenseUrlConfig();

		$this->SetLeftMargin(25);
		$this->printLicense($footerConfig, $translationsConfig, $localeKey, $licenseUrl);

    }

	public function TemplateBody() {
		//GET TEMPLATE BODY CONFIGURATION
		$templateBodyConfig = $this->config->getTemplateBodyConfig();

		// ---------------------------------------------------------------------------------------------------------------------------------- //

		//Print institution LOGO
		$this->printInstitutionLogo($templateBodyConfig['config']['institution_logo']);
			
		//Print journal LOGO
		$this->printJournalLogo($templateBodyConfig['config']['journal_logo']);

		$xPos = $this->getImageRBX();
		$xPos = $xPos + 5;
		$yPos = 18;

		//CREATE JOURNAL TITLE
		if ($templateBodyConfig['metadata']['journal_title']) {
			$this->createNoClickableText(
				$templateBodyConfig['metadata']['journal_title'], 
				$xPos, 
				$yPos, 
				$templateBodyConfig['config']['journal_title']['text_color'], 
				$templateBodyConfig['config']['journal_title']['font']
			);
			$yPos = $yPos + 4;
		}

		// CREATE JOURNAL ISSUE
		if ($templateBodyConfig['metadata']['journal_data']) {
			$this->createNoClickableText(
				$templateBodyConfig['metadata']['journal_data'], 
				$xPos, 
				$yPos, 
				$templateBodyConfig['config']['journal_issue']['text_color'], 
				$templateBodyConfig['config']['journal_issue']['font']
			);
			$yPos = $yPos + 4;
		}

		//CREATE DOI
		if ($templateBodyConfig['metadata']['doi']) {
			$doiUrl = 'https://doi.org/' . $templateBodyConfig['metadata']['doi']; // Construct complete doi URL.
			$this->createClickableText(
				$doiUrl, 
				$doiUrl,
				$xPos, 
				$yPos, 
				$templateBodyConfig['config']['doi']['text_color'], 
				$templateBodyConfig['config']['doi']['font']
			);
			$yPos = $yPos + 4;
		}

		//CREATE ONLINE ISSN
		if ($templateBodyConfig['metadata']['online_issn']) {
			$text = "ISSN " . $templateBodyConfig['metadata']['online_issn'] . ' | ';
			$this->createNoClickableText(
				$text, 
				$xPos, 
				$yPos, 
				$templateBodyConfig['config']['online_issn']['text_color'], 
				$templateBodyConfig['config']['online_issn']['font']
			);

			$xPos += $this->getStringWidth($text);
		}

		//CREATE JOURNAL URL
		if ($templateBodyConfig['metadata']['journal_url']){
			$this->createClickableText(
				$templateBodyConfig['metadata']['journal_url'],
				$templateBodyConfig['metadata']['journal_url'],
				$xPos,
				$yPos,
				$templateBodyConfig['config']['journal_url']['text_color'],
				$templateBodyConfig['config']['journal_url']['font']
			);
			$yPos = $yPos + 4;
		}

		if ($templateBodyConfig['metadata']['editorial']) {
			$this->createNoClickableText(
				$templateBodyConfig['metadata']['editorial'],
				$xPos, 
				$yPos, 
				$templateBodyConfig['config']['editorial']['text_color'], 
				$templateBodyConfig['config']['editorial']['font']
			);
		}

		//Print first line
		$this->Line(0, 45, 150, 45);
		
		$this->SetLeftMargin(25);
		$this->Ln(30);

		// Article titles
		$this->createTitlesAndSubtitles(
			$this->GetX(), 
			$this->GetY(), 
			$this->config->getTitlesConfig(),
			$this->config->getSubtitlesConfig(),
			$templateBodyConfig['metadata']['locale_key']
		);

		$this->SetFillColor(0, 0, 0); 
		
		$this->createAuthorsData(
			$this->config->getAuthorsConfig(), 
			$this->GetX(),
			$this->GetY(),
			$templateBodyConfig['metadata']['locale_key']
		);

		$this->Ln(5);
		//Print second line
		$this->Line($this->GetX(), $this->GetY(), $this->GetX() + 155, $this->GetY());
		$this->SetRightMargin(30);
		$this->Ln(5);

		$this->createAbstractsAndKeywords(
			$this->config->getKeywordsConfig(),
			$this->config->getAbstractConfig(),
			$templateBodyConfig['metadata']['translations_config'],
			$this->GetX(),
			$this->GetY(),
			$templateBodyConfig['metadata']['locale_key']
		);

		$this->createDates(
			$this->config->getDatesConfig(), 
			$templateBodyConfig['metadata']['translations_config'], 
			$templateBodyConfig['metadata']['locale_key'], 
			$this->GetX(), 
			$this->GetY()
		);

		$this->Ln(25);

		$this->SetLeftMargin(15);

		$this->AddPage();
	}

	public function Body(){
		$bodyConfig = $this->config->getBodyConfig();
		$htmlString = $this->config->getHtmlString();
		$pluginPath = $this->config->getPluginPath();

		$this->SetLeftMargin(27);
		$this->SetRightMargin(27);

		$this->SetFont($bodyConfig['config']['font']['family'], $bodyConfig['config']['font']['style'], $bodyConfig['config']['font']['size']);

		$htmlString .= "\n" . '<style>' . "\n" . file_get_contents($pluginPath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'styles' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'pdfGalley.css') . '</style>';
		$htmlString = PDFBodyHelper::_prepareForPdfGalley($htmlString, $this->config);
		$this->writeHTML($htmlString, true, false, true, false, 'J');
	}


	public function printLicense($footerConfig, $translationsConfig, $localeKey, $licenseUrl): void {
		if ($licenseUrl) {
			foreach ($footerConfig['config']['licenses']['links'] as $license => $licenseLink) {
				if ($licenseUrl === $licenseLink) {
					$licenseLogoPath = $footerConfig['config']['licenses']['logos'][$license]; 
					$this->Image(
						$licenseLogoPath, 
						$this->GetX(), 
						$this->GetY() - 1, 
						$footerConfig['config']['licenses']['logo_width'], 
						$footerConfig['config']['licenses']['logo_height'], 
						'', 
						$licenseLink, 
						'L'
					);

					$xPosition = $this->getImageRBX() + 2;
					$translationText = $this->getTranslationByKey($translationsConfig, $localeKey, 'license_text') . ' ' . $license;
					$this->createClickableText(
						$licenseLink, 
						$translationText, 
						$xPosition, 
						$this->GetY() + 0.5, 
						$footerConfig['config']['licenses']['text_color'], 
						$footerConfig['config']['licenses']['font']
					);
				}
			}
		}
	}

	public function createTitlesAndSubtitles($xPosition, $yPosition, $titlesConfig, $subtitlesConfig, $localeKey): void {
		$this->SetXY($xPosition, $yPosition);
		
		$this->SetFont($titlesConfig['titles_config']['principal_title_font']['family'], $titlesConfig['titles_config']['principal_title_font']['style'], $titlesConfig['titles_config']['principal_title_font']['size']);
		$this->SetTextColor($titlesConfig['titles_config']['principal_title_color'][0], $titlesConfig['titles_config']['principal_title_color'][1], $titlesConfig['titles_config']['principal_title_color'][2]);
		$this->Write(5, $titlesConfig['titles_texts'][$localeKey]);
		unset($titlesConfig['titles_texts'][$localeKey]);

		$this->Ln(7);

		$this->SetFont($subtitlesConfig['subtitles_config']['principal_subtitle_font']['family'], $subtitlesConfig['subtitles_config']['principal_subtitle_font']['style'], $subtitlesConfig['subtitles_config']['principal_subtitle_font']['size']);
		$this->SetTextColor($subtitlesConfig['subtitles_config']['principal_subtitle_color'][0], $subtitlesConfig['subtitles_config']['principal_subtitle_color'][1], $subtitlesConfig['subtitles_config']['principal_subtitle_color'][2]);
		$this->Write(5, $subtitlesConfig['subtitles_texts'][$localeKey]);
		unset($subtitlesConfig['subtitles_texts'][$localeKey]);

		$this->Ln(10);

		foreach ($titlesConfig['titles_texts'] as $language => $title) {
			$text = $title . '. ' . $subtitlesConfig['subtitles_texts'][$language];
			$this->createNoClickableText($text, $this->GetX(), $this->GetY(), $titlesConfig['titles_config']['text_color'], $titlesConfig['titles_config']['font']);
			$this->Ln(3);
		}

		$this->Ln(5);

	}

	public function createAbstractsAndKeywords($keywordsConfig, $abstractConfig, $translationsConfig, $xPosition, $yPosition, $localeKey) {
		$this->SetXY($xPosition, $yPosition);
	
		// Primero imprimir el idioma original
		if (isset($abstractConfig['abstract_texts'][$localeKey])) {
			$this->printAbstractAndKeywords($keywordsConfig, $abstractConfig, $translationsConfig, $xPosition, $localeKey);
			$this->Ln(10);
			unset($abstractConfig['abstract_texts'][$localeKey]);
			unset($keywordsConfig['keywords_texts'][$localeKey]);
		}

		foreach ($abstractConfig['abstract_texts'] as $language => $abstract) {
			$this->printAbstractAndKeywords($keywordsConfig, $abstractConfig, $translationsConfig, $xPosition, $language);
			$this->Ln(10);
		}
	}

	public function printAbstractAndKeywords($keywordsConfig, $abstractConfig, $translationsConfig, $xPosition, $language) {
		$abstractTitle = $this->getTranslationByKey($translationsConfig, $language, 'abstract') . ':';
		$abstractText = strip_tags($abstractConfig['abstract_texts'][$language]); //Delete HTML tags of abstract
		$keywordsTitle = $this->getTranslationByKey($translationsConfig, $language, 'keywords') . ':';
		$keywords = is_array($keywordsConfig['keywords_texts'][$language]) ? implode(', ', $keywordsConfig['keywords_texts'][$language]) : "Error processing keywords in [$language]";

		$this->SetFont($abstractConfig['abstract_title_font']['family'], $abstractConfig['abstract_title_font']['style'], $abstractConfig['abstract_title_font']['size']);
		$this->SetTextColor($abstractConfig['abstract_title_color'][0], $abstractConfig['abstract_title_color'][1], $abstractConfig['abstract_title_color'][2]);
		$this->Write(5, $abstractTitle);

		$this->SetFont($abstractConfig['abstract_text_font']['family'], $abstractConfig['abstract_text_font']['style'], $abstractConfig['abstract_text_font']['size']);
		$this->SetTextColor($abstractConfig['abstract_text_color'][0], $abstractConfig['abstract_text_color'][1], $abstractConfig['abstract_text_color'][2]);
		$this->SetX($xPosition + $this->GetStringWidth($abstractTitle) + 3);
		$this->Write(5, $abstractText, '', false, 'L');
			
		$this->Ln(7);

		$this->SetFont($keywordsConfig['keywords_title_font']['family'], $keywordsConfig['keywords_title_font']['style'], $keywordsConfig['keywords_title_font']['size']);
		$this->SetTextColor($keywordsConfig['keywords_title_color'][0], $keywordsConfig['keywords_title_color'][1], $keywordsConfig['keywords_title_color'][2]);
		$this->Write(5, $keywordsTitle);

		$this->SetFont($keywordsConfig['keywords_font']['family'], $keywordsConfig['keywords_font']['style'], $keywordsConfig['keywords_font']['size']);
		$this->SetTextColor($keywordsConfig['keywords_color'][0], $keywordsConfig['keywords_color'][1], $keywordsConfig['keywords_color'][2]);
		$this->SetX($xPosition + $this->GetStringWidth($keywordsTitle) + 4);
		$this->Write(5, $keywords);
	}

	//CREATE A BLOCK WITH ALL DATES (SUBMITTED, ACCEPTED, PUBLISHED).
	public function createDates(array $datesConfig, Array $translationsConfig, $localeKey, $xPosition, $yPosition): void {
		$this->SetXY($xPosition, $yPosition);
		
		$acceptedText = $this->getTranslationByKey($translationsConfig, $localeKey, 'accepted') . ': ' . $datesConfig['date_accepted'];
		$submittedText = $this->getTranslationByKey($translationsConfig, $localeKey, 'received') . ': ' . $datesConfig['date_submitted'];
		
		if ($datesConfig['date_published']) {
			$publishedText = $this->getTranslationByKey($translationsConfig, $localeKey, 'published') . ': ' . $datesConfig['date_published'];
		} else {
			$publishedText = '';
		}

		$datesText = trim(implode(' - ', array_filter([$submittedText, $acceptedText, $publishedText])));

		$this->SetFont($datesConfig['dates_font']['family'], $datesConfig['dates_font']['style'], $datesConfig['dates_font']['size']);
		$this->SetTextColor($datesConfig['dates_color'][0], $datesConfig['dates_color'][1], $datesConfig['dates_color'][2]);
		$this->Ln(10);
		$this->Cell(0, 10, $datesText, 0, 1, 'L');
		$this->SetTextColor(0, 0, 0);
	}

	/* */
	public function getTranslationByKey(Array $translationsConfig, $language, $key) {
		return isset($translationsConfig[$language][$key])
			? $translationsConfig[$language][$key] 
			: "Translation for $key not found in language $language";
	}

	//CREATE BLOCK WITH AUTHOR INFORMATION
	public function createAuthorsData(Array $authorsConfig , $xPosition, $yPosition, $localeKey){
		$this->SetXY($xPosition, $yPosition);
		if (count($authorsConfig['authors_data']) > 0) {
			foreach ($authorsConfig['authors_data'] as $author) {
				
				// Author's bold name
				$this->SetFont(
					$authorsConfig['authors_config']['fullname_font']['family'], 
					$authorsConfig['authors_config']['fullname_font']['style'], 
					$authorsConfig['authors_config']['fullname_font']['size']
				); 
				$this->SetTextColor(
					$authorsConfig['authors_config']['fullname_text_color'][0],
					$authorsConfig['authors_config']['fullname_text_color'][1], 
					$authorsConfig['authors_config']['fullname_text_color'][2]
				);
				$authorName = htmlspecialchars($author->getGivenName($localeKey)) . ' ' . htmlspecialchars($author->getFamilyName($localeKey));
				$this->MultiCell(0, 0, $authorName, 0, 'L', false, 1, '', '', true);

				// ORCID CLICKABLE LOGO
				if (htmlspecialchars($author->getOrcid())) {
					$xLogo = $this->GetX() + $this->GetStringWidth($authorName) + 2;
					$yLogo = $this->GetY() - 3.65;
					$this->getClickableOrcidLogo($xLogo, $yLogo, 3, $author->getOrcid());	
				}

				// Email
				if ($author->getEmail()) {
					$email = htmlspecialchars($author->getEmail());
					$this->SetFont(
						$authorsConfig['authors_config']['email_font']['family'], 
						$authorsConfig['authors_config']['email_font']['style'], 
						$authorsConfig['authors_config']['email_font']['size']
					);
					$this->SetTextColor(
						$authorsConfig['authors_config']['email_text_color'][0], 
						$authorsConfig['authors_config']['email_text_color'][1], 
						$authorsConfig['authors_config']['email_text_color'][2]
					);
					$this->Write(5, $email, 'mailto:' . $email);
					$this->Ln(5);
				}

				// Affiliation
				if ($author->getAffiliation($localeKey)) {
					$affiliation = htmlspecialchars($author->getAffiliation($localeKey));
					$this->SetFont(
						$authorsConfig['authors_config']['affiliation_font']['family'], 
						$authorsConfig['authors_config']['affiliation_font']['style'], 
						$authorsConfig['authors_config']['affiliation_font']['size']
					);
					$this->SetTextColor(
						$authorsConfig['authors_config']['affiliation_text_color'][0], 
						$authorsConfig['authors_config']['affiliation_text_color'][1], 
						$authorsConfig['authors_config']['affiliation_text_color'][2]
					);
					$this->MultiCell(0, 0, $affiliation, 0, 'L', false, 1, '', '', true);
				}

				// Space between authors
				$this->SetTextColor(0, 0, 0);
				$this->Ln(3); 
			}
		}	
	}

	public function getClickableOrcidLogo($x, $y, $size, $orcidLink): void {
		$orcidLogoPath = '/var/www/html/plugins/generic/jatsParser/JATSParser/logo/orcid.png';
		if (file_exists($orcidLogoPath)) {
			$this->Image($orcidLogoPath, $x, $y, $size, $size, '', $orcidLink);
		}
	}
	
	//CREATE ANY TEXT (NO CLICKABLE)
	public function createNoClickableText($text, $xPosition, $yPosition, $textColor, $textFont, $align = 'L'): void{
		$this->SetXY($xPosition, $yPosition);
		$this->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
		$this->SetFont($textFont['family'], $textFont['style'], $textFont['size']);
		$this->MultiCell(0, 5, $text, 0, $align);
		$this->SetTextColor(0, 0, 0);
	}

	//CREATE ANY TEXT (CLICKABLE)
	public function createClickableText($url, $visibleText, $xPosition, $yPosition, $textColor, $textFont, $align = ''): void{
		$this->SetXY($xPosition, $yPosition);
		$this->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
		$this->SetFont($textFont['family'], $textFont['style'], $textFont['size']);
		$this->Write(0, $visibleText, $url, false, $align);
		$this->SetTextColor(0, 0, 0);
	}

	//RETURN A STRING OF AUTHORS. FOR EXAMPLE: | Lionel Messi | LeBron James |
	public function getStringAuthors($authors, $localeKey) {
		if (count($authors) > 0) {
			$authorsNames = '| ';
			foreach ($authors as $author) {
				$authorsNames .= htmlspecialchars($author->getGivenName($localeKey)) . ' ' . htmlspecialchars($author->getFamilyName($localeKey) . ' | ');
			}
		}
		return $authorsNames;
	}

	//CREATE A FORM IN PDF
	public function createForm(Array $formConfig): void {
		$this->SetFillColor($formConfig['color'][0], $formConfig['color'][1], $formConfig['color'][2]);
		$this->Rect($formConfig['x_pos'], $formConfig['y_pos'], $formConfig['width'], $formConfig['height'], 'F');
		$this->SetFillColor($formConfig['fill_color'][0], $formConfig['fill_color'][1], $formConfig['fill_color'][2]);	
	}

	public function printInstitutionLogo(Array $institutionLogoConfig): void {

		$logoFile = glob($institutionLogoConfig['institution_logo_path'] . "institution.*");
		if (!empty($logoFile)) {
			$logoPath = $logoFile[0];
		}

		if ($logoPath && file_exists($logoPath)) {
			$this->Image(
				$logoPath, 
				$institutionLogoConfig['x_pos'], 
				$institutionLogoConfig['y_pos'], 
				$institutionLogoConfig['width']
			);
		}
	}

	public function printJournalLogo(Array $journalLogoConfig): void {
		$path = $journalLogoConfig['journal_logo_public_path'];
		if ($path && file_exists($path)) {
			$this->Image($path, $journalLogoConfig['x_pos'], $journalLogoConfig['y_pos'], $journalLogoConfig['width']);
		}
	}

}