<?php namespace JATSParser\PDF\Templates\TemplateOne\Components;

use JATSParser\PDF\Templates\GenericComponent;

class Footer extends GenericComponent{

        public function render(){
            //GET FOOTER CONFIGURATION
            $footerConfig = $this->config->getFooterConfig();
            $translationsConfig = $this->config->getTranslationsConfig();
            $localeKey = $this->config->getLocaleKeyConfig();
            $licenseUrl = $this->config->getLicenseUrlConfig();
                
            $this->pdfTemplate->SetLeftMargin(25);
            $this->pdfTemplate->printLicense($footerConfig, $translationsConfig, $localeKey, $licenseUrl);
        }

    }