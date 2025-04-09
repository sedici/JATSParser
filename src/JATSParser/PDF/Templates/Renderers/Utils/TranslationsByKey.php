<?php namespace JATSParser\PDF\Templates\Renderers\Utils;

class TranslationsByKey {
    
    /**
     * Retrieves the translation for a given key in a specified language.
     *
     * @param array $translationsConfig The translations configuration array.
     * @param string $language The language code (e.g., 'en_US', 'es_ES').
     * @param string $key The key for which to retrieve the translation.
     * @return string The translated text or an error message if not found.
     */

    public static function getTranslationByKey(Array $translationsConfig, $language, $key) {
        return isset($translationsConfig[$language][$key])
            ? $translationsConfig[$language][$key] 
            : "Translation for $key not found in language $language";
    }
}