<?php namespace JATSParser\PDF\PDFConfig;

    class Translations {
        private static $translationsConfig = [
            'en_US' => [
                'abstract' => 'Abstract',
                'received' => 'Received',
                'accepted' => 'Accepted',
                'published' => 'Published',
                'keywords' => 'Keywords',
                'license_text' => 'This work is under a Creative Commons License',
                'references_sections_separator' => '&',
                'number' => 'No.',
                'volume' => 'Vol.',
                'references' => 'References',
                'footnotes' => 'Footnotes',
            ],
            'es_ES' => [
                'abstract' => 'Resumen',
                'received' => 'Recibido',
                'accepted' => 'Aceptado',
                'published' => 'Publicado',
                'keywords' => 'Palabras clave',
                'license_text' => 'Esta obra está bajo una Licencia Creative Commons',
                'references_sections_separator' => 'y',
                'number' => 'Núm.',
                'volume' => 'Vol.',
                'references' => 'Referencias',
                'footnotes' => 'Notas',
            ],
            'pt_BR' => [
                'abstract' => 'Resumo',
                'received' => 'Recebido',
                'accepted' => 'Aceito',
                'published' => 'Publicado',
                'keywords' => 'Palavras chave',
                'license_text' => 'Este trabalho está sob uma licença Creative Commons',
                'references_sections_separator' => 'e',
                'number' => 'n.',
                'volume' => 'Vol.',
                'references' => 'Referências',
                'footnotes' => 'Notas',
            ],
            'fr_CA' => [
                'abstract' => 'Résumé',
                'received' => 'Reçu',
                'accepted' => 'Accepté',
                'published' => 'Publié',
                'keywords' => 'Mots-clés',
                'license_text' => 'Cette œuvre est sous licence Creative Commons',
                'references_sections_separator' => 'et',
                'references' => 'Références',
                'footnotes' => 'Notes',
            ],
            'fr_FR' => [
                'abstract' => 'Résumé',
                'received' => 'Reçu',
                'accepted' => 'Accepté',
                'published' => 'Publié',
                'keywords' => 'Mots-clés',
                'license_text' => 'Cette œuvre est sous licence Creative Commons',
                'references_sections_separator' => 'et',
                'references' => 'Références',
                'footnotes' => 'Notes',
            ],
        ];

        public static function getTranslations(){
            return self::$translationsConfig;
        }
    }