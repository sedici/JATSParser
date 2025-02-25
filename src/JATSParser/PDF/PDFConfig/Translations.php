<?php

namespace JATSParser\PDF\PDFConfig;

    class Translations {
        private static $translationsConfig = [
            'en_EN' => [
                'abstract' => 'Abstract',
                'received' => 'Received',
                'accepted' => 'Accepted',
                'published' => 'Published',
                'keywords' => 'Keywords',
                'license_text' => 'This work is under a Creative Commons License',
                'references_sections_separator' => '&'
            ],
            'en_US' => [
                'abstract' => 'Abstract',
                'received' => 'Received',
                'accepted' => 'Accepted',
                'published' => 'Published',
                'keywords' => 'Keywords',
                'license_text' => 'This work is under a Creative Commons License',
                'references_sections_separator' => '&'
            ],
            'es_ES' => [
                'abstract' => 'Resumen',
                'received' => 'Recibido',
                'accepted' => 'Aceptado',
                'published' => 'Publicado',
                'keywords' => 'Palabras clave',
                'license_text' => 'Esta obra está bajo una Licencia Creative Commons',
                'references_sections_separator' => 'y'
            ],
            'pt_BR' => [
                'abstract' => 'Resumo',
                'received' => 'Recebido',
                'accepted' => 'Aceito',
                'published' => 'Publicado',
                'keywords' => 'Palavras chave',
                'license_text' => 'Este trabalho está sob uma licença Creative Commons',
                'references_sections_separator' => 'e'
            ],
        ];

        public static function getTranslations(){
            return self::$translationsConfig;
        }
    }