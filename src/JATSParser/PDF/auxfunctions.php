<?php

		//CONFIGURATION OF HEADER LOGOS (INSTITUTION & BRAND)
		public function checkHeaderLogos(Array $logoConfig){
			//Verify if a journal logo exists in a specific directory:
			$logoPath = null;
			$logoFile = glob($logoConfig['journal_logo_data'] . "logo.*");
			if (!empty($logoFile)) {
				$logoPath = $logoFile[0];
			}
		
			// If the specific journal logo exists in the "/var/www/files/journals/" directory, process that logo
			if ($logoPath && file_exists($logoPath)) {
				$imgtype = \TCPDF_IMAGES::getImageFileType($logoPath);
				if (($imgtype === 'eps') OR ($imgtype === 'ai')) {
					$this->ImageEps($logoPath, $logoConfig['x_pos_left'], $logoConfig['y_pos_up'], $logoConfig['default_width']);
				} elseif ($imgtype === 'svg') {
					$this->ImageSVG($logoPath, $logoConfig['x_pos_left'], $logoConfig['y_pos_up'], $logoConfig['default_width']);
				} else {
					$this->Image($logoPath, $logoConfig['x_pos_left'], $logoConfig['y_pos_up'], $logoConfig['default_width']);
				}
				$imgy = $this->getImageRBY();
			} else {
				$imgy = $this->y;
			}
		
			// Using the .jpg logo of an institution to generate ALL pdfs with this brand. This logo is located in: "/jatsParser/JATSParser/logo/".
			if (file_exists($logoConfig['institution_logo_path'])) {
				$this->Image($logoConfig['institution_logo_path'], $logoConfig['x_pos_right'], $logoConfig['y_pos_up'], $logoConfig['default_width']);
				$imgy = $this->getImageRBX();
			}

			return $imgy;	
		}

		//CREATE LINKABLE LICENCE IN PDF
		if ($templateBodyConfig['metadata']['license_url']) {

			$licenseText = $this->getTranslationByKey(
				$templateBodyConfig['metadata']['translations_config'], 
				$templateBodyConfig['metadata']['locale_key'], 'license_text'
			);
			$this->createClickableText(
				$templateBodyConfig['metadata']['license_url'], 
				$licenseText, 
				$xMetadata, 
				$yMetadata, 
				$templateBodyConfig['config']['license']['text_color'], 
				$templateBodyConfig['config']['template_body_font']
			);
			$yMetadata = $yMetadata + 5;
		}

		//CREATE ARTICLE TITLE IN PDF
		if ($templateBodyConfig['metadata']['article_title']) {
			
			$this->createNoClickableText(
				$templateBodyConfig['metadata']['article_title'], 
				$xMetadata, 
				$yMetadata, 
				$templateBodyConfig['config']['article_title']['text_color'], 
				$templateBodyConfig['config']['template_body_font']
			);
			$yMetadata = $yMetadata + 5;
		}

		//CREATE AUTHORS IN PDF. For example: | Lionel Messi | Lebron James |
		if ($templateBodyConfig['metadata']['authors']) {

			$textAuthors = $this->getStringAuthors(
				$templateBodyConfig['metadata']['authors'], 
				$templateBodyConfig['metadata']['locale_key']
			);
			$this->createNoClickableText(
				$textAuthors, 
				$xMetadata, 
				$yMetadata, 
				$templateBodyConfig['config']['authors']['text_color'], 
				$templateBodyConfig['config']['template_body_font']
			);
			$yMetadata = $yMetadata + 5;
		}

		//CREATE JOURNAL AND ISSUE TEXT IN PDF. FOR EXAMPLE: Journal title test, (2)
		if ($templateBodyConfig['metadata']['journal_title'] && $templateBodyConfig['metadata']['journal_issue']) {
			$journalAndIssueText = $templateBodyConfig['metadata']['journal_title'] . ', (' . $templateBodyConfig['metadata']['journal_issue'] . ') ';
			$this->createNoClickableText(
				$journalAndIssueText, 
				$xMetadata, 
				$yMetadata, 
				$templateBodyConfig['config']['journal_issue']['text_color'], 
				$templateBodyConfig['config']['template_body_font']
			);
			$yMetadata = $yMetadata + 5;
		}

		//CREATE ISSN WITH ISSN IN PDF. FOR EXAMPLE: ISSN 0123-0123
		if ($templateBodyConfig['metadata']['online_issn']) {
			$textIssn = 'ISSN ' . $templateBodyConfig['metadata']['online_issn'];
			$this->createNoClickableText(
				$textIssn, 
				$xMetadata, 
				$yMetadata, 
				$templateBodyConfig['config']['online_issn']['text_color'], 
				$templateBodyConfig['config']['template_body_font']
			);
			$yMetadata = $yMetadata + 5;
		}

		// CREATE LINKABLE DOI IN PDF
		if ($templateBodyConfig['metadata']['doi']) {
			$doiUrl = 'https://doi.org/' . $templateBodyConfig['metadata']['doi'];
			$this->createClickableText(
				$doiUrl, 
				$doiUrl, 
				$xMetadata, 
				$yMetadata, 
				$templateBodyConfig['config']['doi']['text_color'], 
				$templateBodyConfig['config']['template_body_font']
			);
			$yMetadata = $yMetadata + 5;
		}


		public function createKeywords(Array $keywordsConfig, Array $translationsConfig, $xPosition, $yPosition) {
			$this->SetXY($xPosition, $yPosition);
			foreach ($keywordsConfig['keywords_texts'] as $language => $keywordArray) {
				$keywordsTitle = $this->getTranslationByKey($translationsConfig, $language, 'keywords');
				$keywords = is_array($keywordArray) ? implode(', ', $keywordArray) : "Error processing keywords in [$language]";
	
				$this->SetFont($keywordsConfig['keywords_title_font']['family'], $keywordsConfig['keywords_title_font']['style'], $keywordsConfig['keywords_title_font']['size']);
				$this->SetTextColor($keywordsConfig['keywords_title_color'][0], $keywordsConfig['keywords_title_color'][1], $keywordsConfig['keywords_title_color'][2],);
				$this->Write(5, $keywordsTitle);
				$this->Ln(10);
				
				$this->SetFont($keywordsConfig['keywords_font']['family'], $keywordsConfig['keywords_font']['style'], $keywordsConfig['keywords_font']['size']);
				$this->SetTextColor($keywordsConfig['keywords_color'][0], $keywordsConfig['keywords_color'][1], $keywordsConfig['keywords_color'][2],);
				$this->Write(5, $keywords);
				$this->Ln(10);
			}
			$this->SetTextColor(0, 0, 0);
		}
	
		//CREATE A BLOCK WITH ALL DATES (SUBMITTED, ACCEPTED, PUBLISHED).
		public function createDates(array $datesConfig, Array $translationsConfig, $localeKey, $xPosition, $yPosition): void {
			$this->SetXY($xPosition, $yPosition);
			
			$acceptedText = $this->getTranslationByKey($translationsConfig, $localeKey, 'accepted') . ': ' . date('d/m/Y', strtotime($datesConfig['date_accepted']));
			$submittedText = $this->getTranslationByKey($translationsConfig, $localeKey, 'received') . ': ' . date('d/m/Y', strtotime($datesConfig['date_submitted']));
			$publishedText = $this->getTranslationByKey($translationsConfig, $localeKey, 'published') . ': ' . date('d/m/Y', strtotime($datesConfig['date_published']));
			$datesText = trim(implode('   ', array_filter([$acceptedText, $submittedText, $publishedText])));
	
			$this->SetFont($datesConfig['dates_font']['family'], $datesConfig['dates_font']['style'], $datesConfig['dates_font']['size']);
			$this->SetTextColor($datesConfig['dates_color'][0], $datesConfig['dates_color'][1], $datesConfig['dates_color'][2]);
			$this->Ln(10);
			$this->Cell(0, 10, $datesText, 0, 1, 'C');
			$this->SetTextColor(0, 0, 0);
		}


		public function Header() {
			if ($this->header_xobjid === false) {
				//GET HEADER CONFIGURATION
				$headerConfig = $this->config->getHeaderConfig();
							
				//GET GLOBAL COLOR CONFIG
				$blackColor = $this->config->getBlackColor();
				$primaryColor = $this->config->getPrimaryColor();
	
				// start a new XObject Template
				$this->header_xobjid = $this->startTemplate($this->w, $this->tMargin);
	
				$this->y = 1;
				
				if ($this->rtl) {
					$this->x = $this->w - $this->original_rMargin;
				} else {
					$this->x = $this->original_lMargin;
				}
	
				$imgy = $this->checkHeaderLogos($headerConfig['config']['header_logo_data']);
	
				// set starting margin for text data cell
				if ($this->getRTL()) {
					$xPosition = $this->original_rMargin + ($headerConfig['config']['header_logo_data']['default_width'] * 1.2);
				} else {
					$xPosition = $this->original_lMargin + ($headerConfig['config']['header_logo_data']['default_width'] * 1.2);
				}
	
				$yPosition = $this->GetY();
	
				// CREATE JOURNAL TITLE 
				if ($headerConfig['metadata']['journal_title']) {
					$this->createNoClickableText(
						$headerConfig['metadata']['journal_title'], 
						$xPosition, 
						$yPosition, $blackColor, 
						$headerConfig['config']['header_font']['title']
					);
					$yPosition = $this->GetY();
				}
				
				// CREATE JOURNAL ISSUE
				if ($headerConfig['metadata']['journal_data']) {
					$this->createNoClickableText(
						$headerConfig['metadata']['journal_data'], 
						$xPosition, 
						$yPosition, 
						$blackColor, 
						$headerConfig['config']['header_font']['default']
					);
					$yPosition = $yPosition + 5;
				}
	
				//CREATE DOI
				if ($headerConfig['metadata']['doi']) {
					$doiUrl = 'https://doi.org/' . $headerConfig['metadata']['doi']; // Construct complete doi URL.
					$this->createClickableText(
						$doiUrl, 
						$doiUrl,
						$xPosition, 
						$yPosition, 
						$primaryColor, 
						$headerConfig['config']['header_font']['default']
					);
					$yPosition = $yPosition + 5;
				}
	
				// CREATE AUTHORS
				if ($headerConfig['metadata']['authors']) {
					
					$authorsText = $this->getStringAuthors($headerConfig['metadata']['authors'], $headerConfig['metadata']['locale_key']);
					
					$this->createNoClickableText(
						$authorsText, 
						$xPosition, 
						$yPosition, 
						$blackColor, 
						$headerConfig['config']['header_font']['default']
					);
					$yPosition = $yPosition + 5;
				}
	
				// print an ending header line
				$this->SetLineStyle(array(
					'width' => $headerConfig['config']['end_line']['width'] / $this->k, 
					'cap' => 'butt', 
					'join' => 'miter', 
					'dash' => 0, 'color' => $headerConfig['config']['end_line']['color']
				));
	
				$this->SetY((5.835 / $this->k) + max($imgy, $this->y));
				if ($this->rtl) {
					$this->SetX($this->original_rMargin);
				} else {
					$this->SetX($this->original_lMargin);
				}
				$this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 0, '', 'T', 0, 'C');
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
	