<?php

namespace JATSParser\PDF\PDFConfig;

class Configuration {
    private $config = [];

    public static $supportedCustomCitationStyles = ['apa']; //make sure to add the supported citation styles here in LOWERCASE.

    public function __construct($metadata) {
        $this->config = [
            'font' => [
                'default' => ['family' => 'helvetica', 'style' => '', 'size' => 10],
                'bold' => ['family' => 'helvetica', 'style' => 'B', 'size' => 10],
                'title' => ['family' => 'helvetica', 'style' => 'BI', 'size' => 12],
            ],
            'color' => [
                'primary' => [0, 64, 53],
                'black' => [0, 0, 0],
                'white' => [255, 255, 255],
            ],
            'metadata' => $metadata,
            'header' => [
                'end_line' => [
                    'color' => [0, 64, 53],
                    'width' => 0.85,
                ],
                'header_data' => [
                    'font' => ['family' => 'helvetica', 'style' => 'B', 'size' => 8],
                    'text_color' => [49, 132, 155],
                ],
                'doi' => [
                    'font' => ['family' => 'helvetica', 'style' => '', 'size' => 8],
                    'text_color' => [49, 132, 155],
                ]
            ],
            'footer' => [
                'left_margin' => 25,
                'footer_font' => [
                    'default' => ['family' => 'helvetica', 'style' => '', 'size' => 10],
                    'bold' => ['family' => 'helvetica', 'style' => 'B', 'size' => 10],
                ],
                'licenses' => [
                    'font' => ['family' => 'philosopher', 'style' => '', 'size' => 7.5],
                    'text_color' => [49, 132, 155],
                    'logo_height' => 6,
                    'logo_width' => 17,
                    'links' => [
                        'CC-BY' => 'https://creativecommons.org/licenses/by/4.0/',
                        'CC-BY-NC' => 'https://creativecommons.org/licenses/by-nc/4.0/',
                        'CC-BY-ND' => 'https://creativecommons.org/licenses/by-nd/4.0/',
                        'CC-BY-SA' => 'https://creativecommons.org/licenses/by-sa/4.0/',
                        'CC-BY-NC-ND' => 'https://creativecommons.org/licenses/by-nc-nd/4.0/',
                        'CC-BY-NC-SA' => 'https://creativecommons.org/licenses/by-nc-sa/4.0/',
                        'CC-ZERO' => 'https://creativecommons.org/publicdomain/zero/1.0/'
                    ],
                    'logos' => [
                        'CC-BY' => '/var/www/html/plugins/generic/jatsParser/JATSParser/logo/creativecommons/cc-by.png',
                        'CC-BY-NC' => '/var/www/html/plugins/generic/jatsParser/JATSParser/logo/creativecommons/cc-by-nc.png',
                        'CC-BY-ND' => '/var/www/html/plugins/generic/jatsParser/JATSParser/logo/creativecommons/cc-by-nd.png',
                        'CC-BY-SA' => '/var/www/html/plugins/generic/jatsParser/JATSParser/logo/creativecommons/cc-by-sa.png',
                        'CC-BY-NC-ND' => '/var/www/html/plugins/generic/jatsParser/JATSParser/logo/creativecommons/cc-by-nc-nd.png',
                        'CC-BY-NC-SA' => '/var/www/html/plugins/generic/jatsParser/JATSParser/logo/creativecommons/cc-by-nc-sa.png',
                        'CC-ZERO' => '/var/www/html/plugins/generic/jatsParser/JATSParser/logo/creativecommons/cc0.png'
                    ]
                ]
            ],
            'template_body' => [
                'left_margin' => 25,
                'template_body_font' => ['family' => 'helvetica', 'style' => '', 'size' => 10],
                'institution_logo' => [
                    'institution_logo_path' => '/var/www/files/journals/' . $metadata['journal_id'] . '/',
                    'x_pos' => 158,
                    'y_pos' => 38,
                    'width' => 30,
                ],
                'journal_logo' => [
                    'journal_logo_public_path' => $metadata['journal_thumbnail_path'],
                    'journal_logo_path' => "/var/www/files/journals/" . $metadata['journal_id'] . "/",
                    'x_pos' => 25,
                    'y_pos' => 20,
                    'width' => 35,
                ],
                'authors' => [
                    'text_color' => [0, 0, 0],
                    'fullname_font' => ['family' => 'helvetica', 'style' => 'B', 'size' => 11],
                    'fullname_text_color' => [123, 128, 127],
                    'email_font' => ['family' => 'philosopher', 'style' => '', 'size' => 10],
                    'email_text_color' => [61, 145, 191],
                    'affiliation_font' => ['family' => 'philosopher', 'style' => '', 'size' => 10],
                    'affiliation_text_color' => [0, 0, 0]
                ],
                'journal_and_issue' => [
                    'text_color' => [0, 0, 0]
                ],
                'online_issn' => [
                    'text_color' => [0, 0, 0],
                    'font' => ['family' => 'calibri400', 'style' => '', 'size' => 7.5]
                ],
                'journal_title' => [
                    'text_color' => [0, 0, 0],
                    'font' => ['family' => 'calibri400', 'style' => '', 'size' => 7.5]
                ],
                'doi' => [
                    'text_color' => [0, 64, 53],
                    'font' => ['family' => 'calibri400', 'style' => '', 'size' => 7.5]
                ],
                'journal_issue' => [
                    'text_color' => [0, 0, 0],
                    'font' => ['family' => 'calibri400', 'style' => '', 'size' => 7.5]
                ],
                'journal_affiliation' => [
                    'text_color' => [0, 0, 0],
                    'font' => ['family' => 'calibri400', 'style' => '', 'size' => 7.5]
                ],
                'journal_url' => [
                    'text_color' => [0, 64, 53],
                    'font' => ['family' => 'calibri400', 'style' => 'I', 'size' => 7.5]
                ],
                'editorial' => [
                    'text_color' => [0, 0, 0],
                    'font' => ['family' => 'calibri400', 'style' => '', 'size' => 7.5]
                ],
                'full_title' => [
                    'text_color' => [49, 132, 155],
                    'bold_font' => ['family' => 'helvetica', 'style' => 'B', 'size' => 15]
                ],
                'titles' => [
                    'principal_title_font' => ['family' => 'helvetica', 'style' => 'B', 'size' => 15],
                    'principal_title_color' => [49, 132, 155],
                    'text_color' => [49, 132, 155],
                    'font' => ['family' => 'helvetica', 'style' => '', 'size' => 9]
                ],
                'subtitles' => [
                    'principal_subtitle_font' => ['family' => 'helvetica', 'style' => 'B', 'size' => 12],
                    'principal_subtitle_color' => [49, 132, 155],
                    'text_color' => [0, 0, 0],
                    'font' => ['family' => 'helvetica', 'style' => '', 'size' => 9]
                ],
                'abstract' => [
                    'abstract_title_color' => [49, 132, 155],
                    'abstract_title_font' => ['family' => 'helvetica', 'style' => 'B', 'size' => 9.5],
                    'abstract_text_color' => [0, 0, 0],
                    'abstract_text_font' => ['family' => 'philosopher', 'style' => '', 'size' => 10]
                ],
                'dates' => [
                    'dates_font' => ['family' => 'philosopher', 'style' => '', 'size' => 9],
                    'dates_color' => [0, 0, 0]
                ],
                'keywords' => [
                    'keywords_title_font' => ['family' => 'helvetica', 'style' => 'B', 'size' => 9.5],
                    'keywords_title_color' => [49, 132, 155],
                    'keywords_font' => ['family' => 'philosopher', 'style' => '', 'size' => 10],
                    'keywords_color' => [0, 0, 0]
                ],
                'issue' => [
                    'issue_color' => [0, 0, 0]
                ]
            ],
            'body' => [
                'font' => ['family' => 'philosopher', 'style' => '', 'size' => 11],
            ],
            'citation_style' => 'Apa'
        ];
    }

    //GETTERS

    public function getHeaderConfig() {
        return ['config' => $this->config['header'],
                'metadata' => $this->getMetadata()];
    }

    public function getFooterConfig() {
        return ['config' => $this->config['footer'],
                'metadata' => $this->getMetadata()];
    }

    public function getTemplateBodyConfig() {
        return ['config' => $this->config['template_body'],
                'metadata' => $this->getMetadata()];
    }

    public function getBodyConfig() {
        return ['config' => $this->config['body'],
                'metadata' => $this->getMetadata()];
    }

    public function getPrimaryColor() {
        return $this->config['color']['primary'];
    }

    public function getBlackColor() {
        return $this->config['color']['black'];
    }

    public function getConfig() {
        return $this->config;
    }

    public function getMetadata() {
        return $this->config['metadata'];
    }

    public function getPublicationId() {
        return $this->config['metadata']['publication_id'];
    }

    public function getPluginPath(){
        return $this->config['metadata']['plugin_path'];
    }
    
    public function getOrcidUrl() {
        return $this->config['metadata']['orcid_url'];
    }

    public function getHtmlString() {
        return $this->config['metadata']['html_string'];
    }

    public function getTranslationsConfig() {
        return $this->config['metadata']['translations_config'];
    }

    public function getCitationStyle() {
        return $this->config['citation_style'];
    }

    public static function getSupportedCustomCitationStyles() {
        return self::$supportedCustomCitationStyles;
    }

    public function getLicenseUrlConfig() {
        return $this->config['metadata']['license_url'];
    }

    public function getLocaleKeyConfig() {
        return $this->config['metadata']['locale_key'];
    }

    public function getFormConfig() {
        return $this->config['template_body']['form'];
    }

    public function getContributors() {
        return $this->config['metadata']['contributors'];
    }

    public function getSubject() {
        return $this->config['metadata']['subject'];
    }

    public function getFullTitle() {
        return $this->config['metadata']['full_title'];
    }

    public function getTitlesConfig(){
        return [
            'titles_texts' => $this->config['metadata']['titles'],
            'titles_config' => $this->config['template_body']['titles']
        ];
    }

    public function getSubtitlesConfig(){
        return [
            'subtitles_texts' => $this->config['metadata']['subtitles'],
            'subtitles_config' => $this->config['template_body']['subtitles']
        ];
    }

    public function getAuthorsConfig() {
        return [
            'authors_data' => $this->config['metadata']['authors'],
			'authors_config' => $this->config['template_body']['authors']
        ];
    }

    public function getAbstractConfig(){
        return [
            'abstract_texts' => $this->config['metadata']['abstract_texts'],
			'abstract_title_font' => $this->config['template_body']['abstract']['abstract_title_font'],
			'abstract_title_color' => $this->config['template_body']['abstract']['abstract_title_color'],
			'abstract_text_font' => $this->config['template_body']['abstract']['abstract_text_font'],
			'abstract_text_color' => $this->config['template_body']['abstract']['abstract_text_color']
        ];
    }

    public function getKeywordsConfig(){
        return [
            'keywords_texts' => $this->config['metadata']['keywords_texts'],
            'keywords_title_font' => $this->config['template_body']['keywords']['keywords_title_font'],
            'keywords_title_color' => $this->config['template_body']['keywords']['keywords_title_color'],
            'keywords_font' => $this->config['template_body']['keywords']['keywords_font'],
            'keywords_color' => $this->config['template_body']['keywords']['keywords_color']
        ];
    }

    public function getDatesConfig(){
        return [
            'date_submitted' => $this->config['metadata']['date_submitted'],
			'date_published' => $this->config['metadata']['date_published'],
			'date_accepted' => $this->config['metadata']['date_accepted'],
			'dates_font' => $this->config['template_body']['dates']['dates_font'],
			'dates_color' => $this->config['template_body']['dates']['dates_color']
        ];
    }

}