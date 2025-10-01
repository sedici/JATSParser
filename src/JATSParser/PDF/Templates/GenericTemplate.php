<?php namespace JATSParser\PDF\Templates;

require_once(__DIR__ .'/../../../../vendor/tecnickcom/tcpdf/tcpdf.php');

use JATSParser\PDF\Templates\ElementsRenderer;
use JATSParser\PDF\PDFConfig\Configuration;

    abstract class GenericTemplate extends \TCPDF {

        private $baseTemplate;
        private $config;

        private $templateBodyComponent;
        private $bodyComponent;
        private $headerComponent;
        private $footerComponent;

        function __construct($baseTemplate, Configuration $config) {
            $this->baseTemplate = $baseTemplate;
            $this->config = $config;

            
            $this->initializeComponents();

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
    
            $this->TemplateBody();
            $this->setPrintHeader(true);
            $this->addPage();
            
            // Text (goes from JATSParser)
            $this->setCellPaddings(0, 0, 0, 0);
            $this->Body();

        }

        private function initializeComponents() {
            $componentsDefinition = $this->baseTemplate->getComponentsDefinition();

            $this->templateBodyComponent = new $componentsDefinition['templateBody']($this, $this->config);
            $this->headerComponent = new $componentsDefinition['header']($this, $this->config);
            $this->footerComponent = new $componentsDefinition['footer']($this, $this->config);
            $this->bodyComponent = new $componentsDefinition['body']($this, $this->config);
        }

        public function Header(){
            if ($this->header_xobjid === false) {
                // start a new XObject Template
                $this->header_xobjid = $this->startTemplate($this->w, $this->tMargin);
                $this->y = 1;

                $this->headerComponent->render();

                $headerSize = 60;
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

        public function TemplateBody() {
            $this->templateBodyComponent->render();
        }

        public function Footer() {
            $this->footerComponent->render();
        }

        public function Body(){
            $this->bodyComponent->render();
        }

        public function getConfig() {
            return $this->config;
        }

    }