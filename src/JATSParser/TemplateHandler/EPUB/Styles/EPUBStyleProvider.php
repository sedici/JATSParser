<?php

namespace JATSParser\TemplateHandler\EPUB\Styles;

class EPUBStyleProvider
{
    /**
     * Retorna el CSS base para el libro EPUB
     */
    public function getStyles(): string
    {
        $path = __DIR__ . '/default.css';
        return file_exists($path) ? (string) file_get_contents($path) : '';
    }
}