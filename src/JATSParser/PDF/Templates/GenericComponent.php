<?php namespace JATSParser\PDF\Templates;

use JATSParser\PDF\Templates\Renderers\Renderer;
use JATSParser\PDF\PDFConfig\Configuration;

    abstract class GenericComponent {

        protected $config;
        protected $pdfTemplate;

        public function __construct($pdfTemplate, Configuration $config) {
            $this->config = $config;
            $this->pdfTemplate = $pdfTemplate;

        }

        abstract public function render();
    }