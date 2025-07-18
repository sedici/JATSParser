<?php namespace JATSParser\PDF\Templates\TemplateOne\Components;

use JATSParser\PDF\PDFBodyHelper;
use JATSParser\PDF\Templates\GenericComponent;

class Body extends GenericComponent{

    public function render(){
        $bodyFont = $this->config->getFontConfig('philosopher');
        $htmlString = $this->config->getMetadata('html_string');
        $pluginPath = $this->config->getMetadata('plugin_path');
        $leftMargin = $this->config->getMargin('body_left');

        $this->pdfTemplate->SetLeftMargin($leftMargin);
        $this->pdfTemplate->SetRightMargin($leftMargin);
        $this->pdfTemplate->SetFont($bodyFont['family'], $bodyFont['style'], $bodyFont['size']);

        $refs = [];

        $htmlString .= "\n" . '<style>' . "\n" . file_get_contents($pluginPath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'styles' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'pdfGalley.css') . '</style>';
        $htmlString = PDFBodyHelper::_prepareForPdfGalley($htmlString, $this->config, $this->pdfTemplate, $refs);

        file_put_contents(
            __DIR__ . '/debug_output.html',
            $htmlString
        );

        // Extraer referencias reales del HTML usando el nuevo método
        $referencias = $this->extractReferences($htmlString);

        // Extraer endnotes usando el nuevo método
        $endnotes = $this->extractEndnotes($htmlString);

        // Eliminar todo el contenedor de referencias-section y su contenido
        $htmlString = preg_replace('/<div[^>]*class\s*=\s*"[^"]*references-section[^"]*"[^>]*>.*<\/div>/is', '', $htmlString);

        // Eliminar todo el contenedor de footnotes-container y su contenido (greedy)
        $htmlString = preg_replace('/<div[^>]*class\s*=\s*"[^"]*footnotes-container[^"]*"[^>]*>.*<\/div>/is', '', $htmlString);

        // process citations
        $partes = preg_split(
            '/(<table\b[^>]*>(?:(?!<\/table>).)*{{LINK:[^:]+:[^}]+}}(?:(?!<\/table>).)*<\/table>|{{LINK:[^:]+:[^}]+}})/is', $htmlString, -1, PREG_SPLIT_DELIM_CAPTURE);

		file_put_contents(
            __DIR__ . '/debug_output.txt',
            print_r($partes, true) . "\n"
        );

        foreach ($partes as $parte) {
            if (preg_match('/<table\b[^>]*>(?:(?!<\/table>).)*{{LINK:[^:]+:[^}]+}}(?:(?!<\/table>).)*<\/table>/is', $parte)) {
                if (preg_match('/{{LINK:([^:]+):(.+?)}}/', $parte, $match)) {
                    $parte = str_replace('{{LINK:' . $match[1] . ':' . $match[2] . '}}', $match[2], $parte); // 
                    error_log('{{LINK:' . $match[1] . ':' . $match[2] . '}}');
                    $this->pdfTemplate->Ln(3);
                    $this->pdfTemplate->writeHTML($parte, false, false, true, false, '');
                    $this->pdfTemplate->SetLeftMargin($leftMargin); // temporal fix, margin left error
                }
            }
            else if (preg_match('/{{LINK:([^:]+):(.+?)}}/', $parte, $match)) {
                $refId = $match[1];
                $texto = $match[2];
                $this->pdfTemplate->SetTextColor(0, 102, 204); // #0066cc
                $this->pdfTemplate->Write(0, ' ' . $texto . ' ', $refs[$refId], 0);
                $this->pdfTemplate->SetTextColor(0, 0, 0); 
                $this->pdfTemplate->SetLeftMargin($leftMargin); // temporal fix, margin left error
            } else {
                $this->pdfTemplate->writeHTML($parte, false, false, true, false, '');
                $this->pdfTemplate->SetLeftMargin($leftMargin); // temporal fix, margin left error

            }
        }

        //PASO 7
        $this->pdfTemplate->AddPage(); 
        $this->pdfTemplate->SetLeftMargin($leftMargin);
        $this->pdfTemplate->SetFillColor(255, 255, 255); // fondo blanco

        // Título de referencias
        if (!empty($referencias)) {
            $this->pdfTemplate->writeHTML('<h2>' . __('plugins.generic.jatsParser.article.references.title') . '</h2>', false, false, true, false, '');
        }
        
        $this->pdfTemplate->Ln(5);

        foreach ($refs as $refId => $linkId) {
            $this->pdfTemplate->SetLink($linkId); // marcar posición
            if (isset($referencias[$refId])) {
                $this->pdfTemplate->writeHTML($referencias[$refId], false, false, true, false, '');
                $this->pdfTemplate->SetLeftMargin($leftMargin); // temporal fix, margin left error
                $this->pdfTemplate->Ln(10); 
            }
        }

        $this->pdfTemplate->Ln(5);

        // Título de footnotes
        if (!empty($endnotes)) {
            $this->pdfTemplate->writeHTML('<h2>' . __('plugins.generic.jatsParser.article.footnotes.title') . '</h2>', false, false, true, false, '');
        }
        $this->pdfTemplate->Ln(5);

        foreach ($endnotes as $noteId => $noteHtml) {
            $refKey = $noteId;
            if (!isset($refs[$refKey]) && strpos($refKey, 'fn-') === 0) {
                // Si no existe, intenta sin el prefijo
                $refKeyNoFn = substr($refKey, 3);
                if (isset($refs[$refKeyNoFn])) {
                    $this->pdfTemplate->SetLink($refs[$refKeyNoFn]);
                }
            } else if (isset($refs[$refKey])) {
                $this->pdfTemplate->SetLink($refs[$refKey]);
            }
            $this->pdfTemplate->writeHTML($noteHtml, false, false, true, false, '');
            $this->pdfTemplate->SetLeftMargin($leftMargin); // temporal fix, margin left error
            $this->pdfTemplate->Ln(6);
        }
    }

    /**
     * Extrae las referencias del HTML y las retorna como array [id => html]
     */
    private function extractReferences($htmlString) {
        $domRefs = new \DOMDocument();
        libxml_use_internal_errors(true);
        $domRefs->loadHTML('<?xml encoding="utf-8" ?>' . $htmlString);
        libxml_clear_errors();
        $xpathRefs = new \DOMXPath($domRefs);

        $referencias = [];
        foreach ($xpathRefs->query('//li[contains(@class,"citation-item")]') as $li) {
            $refId = $li->getAttribute('id');
            $referencias[$refId] = $domRefs->saveHTML($li);
        }
        return $referencias;
    }

    /**
     * Extrae las endnotes del HTML y las retorna como array [id => html]
     */
    private function extractEndnotes($htmlString) {
        $domNotes = new \DOMDocument();
        libxml_use_internal_errors(true);
        $domNotes->loadHTML('<?xml encoding="utf-8" ?>' . $htmlString);
        libxml_clear_errors();
        $xpathNotes = new \DOMXPath($domNotes);

        $endnotes = [];
        foreach ($xpathNotes->query('//div[contains(@class,"footnote-item")]') as $div) {
            $noteId = $div->getAttribute('id');
            $endnotes[$noteId] = $domNotes->saveHTML($div);
        }
        return $endnotes;
    }
}