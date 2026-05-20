<?php

namespace JATSParser\PDF\PDFConfig;

class Configuration {
    private $metadata = [];
    private $config = [];
    private $orcid_logo = [];
    private $images = [];

    public static $supportedCustomCitationStyles = ['apa'];
    public static $numberedReferencesCitationStyles = ['ieee'];

    public function __construct($metadata) {
        $this->metadata = $metadata;

        $this->orcid_logo = [
            'no_bg' => $metadata['plugin_path'] . '/JATSParser/logo/orcid.png',
            'white_bg' => $metadata['plugin_path'] . '/JATSParser/logo/orcid-white.png',
            'black_bg' => $metadata['plugin_path'] . '/JATSParser/logo/orcid-black.png',
        ];

        $this->images = [
            'not_found' => $metadata['plugin_path'] . '/JATSParser/logo/not_found.png',
        ];
        
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
                'font' => ['family' => 'freeserif', 'style' => '', 'size' => 7.5],
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

    public function getLicenseConfig() {
        return $this->config['licenses'];
    }

    public static function getSupportedCustomCitationStyles() {
        return self::$supportedCustomCitationStyles;
    }

    public static function getNumberedReferences() {
        return self::$numberedReferencesCitationStyles;
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
    
    public function getOrcidLogo() {
        return $this->orcid_logo;
    }

    public function getImages() {
        return $this->images;
    }
}