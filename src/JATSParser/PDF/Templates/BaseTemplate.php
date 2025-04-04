<?php namespace JATSParser\PDF\Templates;

use JATSParser\PDF\Templates\GenericTemplate;
use JATSParser\PDF\PDFConfig\Configuration;

abstract class BaseTemplate extends GenericTemplate {

    private $templateComponents = [];

    function __construct(Configuration $config) {
        $this->registerComponents();
        parent::__construct($this, $config);
    }

    /**
     * Register the components for the template.
     * This method uses reflection to get the namespace of the current class (template)
     * and constructs the component class names based on that.
     */
    private function registerComponents() {
        $reflection = new \ReflectionClass($this);
        $namespace = $reflection->getNamespaceName();

        $this->templateComponents = [
            'templateBody' => $namespace . '\\Components\\TemplateBody',
            'header' => $namespace . '\\Components\\Header',
            'footer' => $namespace . '\\Components\\Footer',
            'body' => $namespace . '\\Components\\Body'
        ];

        foreach ($this->templateComponents as $key => $componentClass) {
            if (!class_exists($componentClass)) {
                throw new \Exception("Component {$componentClass} not found for template " . get_class($this));
            }
        }
    }

    public function getComponentsDefinition() {
        return $this->templateComponents;
    }

}