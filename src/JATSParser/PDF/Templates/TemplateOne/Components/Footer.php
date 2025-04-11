<?php namespace JATSParser\PDF\Templates\TemplateOne\Components;

use JATSParser\PDF\Templates\GenericComponent;
use JATSParser\PDF\Templates\Renderers\SingleRenderer\License;

class Footer extends GenericComponent{

        public function render(){
            //GET FOOTER CONFIGURATION
            $footerConfig = $this->config->getFooterConfig();
            $translationsConfig = $this->config->getTranslationsConfig();
            $localeKey = $this->config->getLocaleKeyConfig();
            $licenseUrl = $this->config->getLicenseUrlConfig();
                
            $this->pdfTemplate->SetLeftMargin(25);
            if ($licenseUrl) {
                License::renderLicense($this->pdfTemplate, $footerConfig, $translationsConfig, $localeKey, $licenseUrl);
            }
        }

    }