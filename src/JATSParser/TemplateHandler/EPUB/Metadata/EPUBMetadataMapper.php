<?php

namespace JATSParser\TemplateHandler\EPUB\Metadata;

class EPUBMetadataMapper
{
    /**
     * Mapea y normaliza los metadatos de OJS a un formato estándar EPUB
     */
    public function map(array $raw, string $localeKey, int $fileId): array
    {
        $lang = $this->resolveLanguage($localeKey);

        return [
            'title'       => $this->resolveTitle($raw, $localeKey),
            'language'    => $lang,
            'identifier'  => $this->resolveIdentifier($raw, $fileId),
            'authors'     => $this->resolveAuthors($raw, $localeKey),
            'publisher'   => $this->resolvePublisher($raw),
            'description' => $this->resolveDescription($raw, $lang),
        ];
    }

    private function resolveLanguage(string $localeKey): string
    {
        return explode('_', $localeKey)[0];
    }

    private function resolveTitle(array $raw, string $localeKey): string
    {
        return $raw['article_title'] 
            ?? $raw['full_title'] 
            ?? $raw['titles'][$localeKey] 
            ?? 'Artículo sin título';
    }

    private function resolveIdentifier(array $raw, int $fileId): string
    {
        if (!empty($raw['doi'])) {
            return 'doi:' . $raw['doi'];
        }

        $submissionId = $raw['publication_id'] ?? $fileId;
        return 'urn:ojs:publication:' . $submissionId;
    }

    private function resolveAuthors(array $raw, string $localeKey): array
    {
        if (empty($raw['authors']) || !is_array($raw['authors'])) {
            return [];
        }

        $authors = [];
        foreach ($raw['authors'] as $authorData) {
            $name = $this->formatAuthorName($authorData, $localeKey);
            if ($name !== '') {
                $authors[] = $name;
            }
        }

        return $authors;
    }

    private function formatAuthorName($authorData, string $localeKey): string
    {
        if (is_string($authorData)) {
            return trim($authorData);
        }

        $given  = $this->extractLocalizedValue($authorData['givenName'] ?? '', $localeKey);
        $family = $this->extractLocalizedValue($authorData['familyName'] ?? '', $localeKey);

        return trim("{$given} {$family}");
    }

    private function extractLocalizedValue($value, string $localeKey): string
    {
        if (is_array($value)) {
            return (string) ($value[$localeKey] ?? reset($value) ?? '');
        }

        return (string) $value;
    }

    private function resolvePublisher(array $raw): string
    {
        return $raw['journal_title'] ?? $raw['editorial'] ?? '';
    }

    private function resolveDescription(array $raw, string $lang): string
    {
        if (empty($raw['abstract_texts'])) {
            return '';
        }

        $abstract = $this->extractLocalizedValue($raw['abstract_texts'], $lang);
        return trim(strip_tags($abstract));
    }
}