<?php namespace JATSParser\PDF\PDFConfig;

    class Translations {
        private static $translationsConfig = [

            'en' => [
                'abstract' => 'Abstract',
                'received' => 'Received',
                'accepted' => 'Accepted',
                'published' => 'Published',
                'keywords' => 'Keywords',
                'license_text' => 'This work is under a Creative Commons License',
                'references_sections_separator' => '&',
                'number' => 'No.',
                'volume' => 'Vol.',
            ],
            'es' => [
                'abstract' => 'Resumen',
                'received' => 'Recibido',
                'accepted' => 'Aceptado',
                'published' => 'Publicado',
                'keywords' => 'Palabras clave',
                'license_text' => 'Esta obra está bajo una Licencia Creative Commons',
                'references_sections_separator' => 'y',
                'number' => 'Núm.',
                'volume' => 'Vol.',
            ],
            'pt' => [
                'abstract' => 'Resumo',
                'received' => 'Recebido',
                'accepted' => 'Aceito',
                'published' => 'Publicado',
                'keywords' => 'Palavras chave',
                'license_text' => 'Este trabalho está sob uma licença Creative Commons',
                'references_sections_separator' => 'e',
                'number' => 'n.',
                'volume' => 'Vol.',
            ],
        ];

        public static function getTranslations(){
            return self::$translationsConfig;
        }
    }