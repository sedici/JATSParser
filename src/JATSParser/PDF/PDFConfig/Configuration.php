<?php

namespace JATSParser\PDF\PDFConfig;

class Configuration {
    private $metadata = [];
    private $config = [];

    public static $supportedCustomCitationStyles = ['apa'];
    public static $numberedReferencesCitationStyles = ['ieee'];

    public function __construct($metadata) {
        $this->metadata = $metadata;
        $this->config = [
            'fonts' => [
                'default' => ['family' => 'freesans', 'style' => '', 'size' => 10],
                'bold' => ['family' => 'helvetica', 'style' => 'B', 'size' => 10],
                'title' => ['family' => 'helvetica', 'style' => 'BI', 'size' => 12],
                'calibri' => ['family' => 'calibri400', 'style' => '', 'size' => 7.5],
                'philosopher' => ['family' => 'philosopher', 'style' => '', 'size' => 10],
                'dejavusans' => ['family' => 'dejavusans', 'style' => '', 'size' => 10],
                'dejavuserif' => ['family' => 'dejavuserif', 'style' => '', 'size' => 10],
                'freeserif' => ['family' => 'freeserif', 'style' => '', 'size' => 10],
                'times' => ['family' => 'times', 'style' => '', 'size' => 10],
                'freesans' => ['family' => 'freesans', 'style' => '', 'size' => 10],
                'symbol' => ['family' => 'symbol', 'style' => '', 'size' => 10],
                'zapfdingbats' => ['family' => 'zapfdingbats', 'style' => '', 'size' => 10],
            ],
            'colors' => [
                'primary' => [0, 64, 53],
                'black' => [0, 0, 0],
                'white' => [255, 255, 255],
                'accent' => [49, 132, 155],
                'url' => [20, 52, 61],
            ],
            'margins' => [
                'footer_left' => 25,
                'body_left' => 25,
            ],
            'logos' => [
                'institution_logo' => [
                    'path' => $metadata['journal_logos_path'] ?? '',
                    'x_pos' => 158,
                    'y_pos' => 38,
                    'width' => 30,
                ],
                'journal_logo' => [
                    'path' => $metadata['journal_logos_path'] ?? '',
                    'x_pos' => 25,
                    'y_pos' => 17,
                    'width' => 35,
                ],
            ],
            'licenses' => [
                'font' => ['family' => 'philosopher', 'style' => '', 'size' => 7.5],
                'text_color' => [49, 132, 155],
                'logo_height' => 6,
                'logo_width' => 17,
                'links' => [
                    'CC-BY' => 'https://creativecommons.org/licenses/by/4.0',
                    'CC-BY-NC' => 'https://creativecommons.org/licenses/by-nc/4.0',
                    'CC-BY-ND' => 'https://creativecommons.org/licenses/by-nd/4.0',
                    'CC-BY-SA' => 'https://creativecommons.org/licenses/by-sa/4.0',
                    'CC-BY-NC-ND' => 'https://creativecommons.org/licenses/by-nc-nd/4.0',
                    'CC-BY-NC-SA' => 'https://creativecommons.org/licenses/by-nc-sa/4.0',
                    'CC-ZERO' => 'https://creativecommons.org/publicdomain/zero/1.0'
                ],
                'logos' => [
                    'CC-BY' => $metadata['plugin_path'] . '/JATSParser/logo/creativecommons/cc-by.png',
                    'CC-BY-NC' => $metadata['plugin_path'] . '/JATSParser/logo/creativecommons/cc-by-nc.png',
                    'CC-BY-ND' => $metadata['plugin_path'] . '/JATSParser/logo/creativecommons/cc-by-nd.png',
                    'CC-BY-SA' => $metadata['plugin_path'] . '/JATSParser/logo/creativecommons/cc-by-sa.png',
                    'CC-BY-NC-ND' => $metadata['plugin_path'] . '/JATSParser/logo/creativecommons/cc-by-nc-nd.png',
                    'CC-BY-NC-SA' => $metadata['plugin_path'] . '/JATSParser/logo/creativecommons/cc-by-nc-sa.png',
                    'CC-ZERO' => $metadata['plugin_path'] . '/JATSParser/logo/creativecommons/cc0.png'
                ]
            ],
        ];
    }

    public function getMetadata($key = null) {
        if ($key === null) return $this->metadata;
        return $this->metadata[$key] ?? null;
    }

    public function getFontConfig($type = 'default', $size = null) {
        $font = $this->config['fonts'][$type] ?? $this->config['fonts']['default'];
        $fontFamily = $font['family'];

        // Check if the font is available in TCPDF
        if (!$this->isTCPDFFontAvailable($fontFamily)) {
            // If the font is not available, log an error and use the default font
            $font = $this->config['fonts']['default'];
        }

        if ($size !== null) {
            $font['size'] = $size;
        }
        return $font;
    }

    /**
     * Verify if a font is available in TCPDF.
     * Searches for any file that contains the font name.
     */
    private function isTCPDFFontAvailable($fontFamily) {
        if (defined('K_PATH_FONTS')) {
            $fontsDir = K_PATH_FONTS;
        } else {
            $fontsDir = __DIR__ . '/../../../../../vendor/tecnickcom/tcpdf/fonts/';
        }
        if (!is_dir($fontsDir)) {
            error_log("TCPDF fonts directory not found: $fontsDir");
            return false;
        }
        $fontFamilyLower = strtolower($fontFamily);
        foreach (glob($fontsDir . '*.php') as $file) {
            
            if (strpos(strtolower(basename($file)), $fontFamilyLower) !== false) {
                return true;
            }
        }
        error_log("Font '$fontFamily' not found in TCPDF fonts directory: $fontsDir");
        return false;
    }

    public function getColorConfig($type = 'primary') {
        return $this->config['colors'][$type] ?? null;
    }

    public function getMargin($type = 'footer_left') {
        return $this->config['margins'][$type] ?? null;
    }

    public function getLogoConfig($type = 'institution_logo') {
        return $this->config['logos'][$type] ?? null;
    }

    public function getLicenseConfig() {
        return $this->config['licenses'];
    }

    public static function getSupportedCustomCitationStyles() {
        return self::$supportedCustomCitationStyles;
    }

    public static function getNumberedReferences() {
        return self::$numberedReferencesCitationStyles;
    }

    public function getDatesConfig() {
        return [
            'date_submitted' => $this->getMetadata('date_submitted'),
            'date_published' => $this->getMetadata('date_published'),
            'date_accepted' => $this->getMetadata('date_accepted'),
            'dates_font' => $this->getFontConfig('calibri'),
            'dates_color' => $this->getColorConfig('black')
        ];
    }

    public function getTitlesConfig() {
        return [
            'titles_texts' => $this->getMetadata('titles'),
            'titles_config' => [
                'principal_title_font' => $this->getFontConfig('bold', 15),
                'principal_title_color' => $this->getColorConfig('accent'),
                'text_color' => $this->getColorConfig('accent'),
                'font' => $this->getFontConfig('default', 10)
            ]
        ];
    }

    public function getSubtitlesConfig() {
        return [
            'subtitles_texts' => $this->getMetadata('subtitles'),
            'subtitles_config' => [
                'principal_subtitle_font' => $this->getFontConfig('bold', 12),
                'principal_subtitle_color' => $this->getColorConfig('accent'),
                'text_color' => $this->getColorConfig('black'),
                'font' => $this->getFontConfig('default', 10)
            ]
        ];
    }

    public function getPrefixesConfig() {
        return [
            'prefixes_texts' => $this->getMetadata('prefixes'),
            'prefixes_config' => [
                'principal_prefix_font' => $this->getFontConfig('bold', 15),
                'principal_prefix_color' => $this->getColorConfig('accent'),
                'text_color' => $this->getColorConfig('black'),
                'font' => $this->getFontConfig('default', 10)
            ]
        ];
    }

    public function getAuthorsConfig() {
        return [
            'authors_data' => $this->getMetadata('authors'),
            'plugin_path' => $this->getMetadata('plugin_path'),
            'authors_config' => [
                'text_color' => $this->getColorConfig('black'),
                'fullname_font' => $this->getFontConfig('bold'),
                'fullname_text_color' => [123, 128, 127],
                'email_font' => $this->getFontConfig('philosopher'),
                'email_text_color' => [61, 145, 191],
                'affiliation_font' => $this->getFontConfig('philosopher'),
                'affiliation_text_color' => $this->getColorConfig('black')
            ]
        ];
    }

    public function getAbstractConfig() {
        return [
            'abstract_texts' => $this->getMetadata('abstract_texts'),
            'abstract_title_font' => $this->getFontConfig('bold'),
            'abstract_title_color' => $this->getColorConfig('accent'),
            'abstract_text_font' => $this->getFontConfig('philosopher'),
            'abstract_text_color' => $this->getColorConfig('black')
        ];
    }

    public function getKeywordsConfig() {
        return [
            'keywords_texts' => $this->getMetadata('keywords_texts'),
            'keywords_title_font' => $this->getFontConfig('bold'),
            'keywords_title_color' => $this->getColorConfig('accent'),
            'keywords_font' => $this->getFontConfig('philosopher'),
            'keywords_color' => $this->getColorConfig('black')
        ];
    }

    public function getContributors() {
        return $this->getMetadata('contributors');
    }

    public function getSubject() {
        return $this->getMetadata('subject');
    }

    public function getFullTitle() {
        return $this->getMetadata('full_title');
    }

    public function getCitationStyle() {
        return $this->getMetadata('citation_style') ?? '';
    }

    public function getPublicationId() {
        return $this->getMetadata('publication_id');
    }

    public function getLocaleKeyConfig() {
        return $this->getMetadata('locale_key');
    }

    // Here you can add more methods to retrieve other configurations or metadata as needed.
}