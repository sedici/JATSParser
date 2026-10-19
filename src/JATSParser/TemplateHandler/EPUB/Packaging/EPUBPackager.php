<?php

namespace JATSParser\TemplateHandler\EPUB\Packaging;

use PHPePub\Core\EPub;

class EPUBPackager
{
    /**
     * Empaqueta todas las partes y compila el binario EPUB 3 final
     */
    public function generate(array $metadata, string $css, string $chapterXhtml, array $resources): string
    {
        $book = $this->initBook($metadata);

        $this->applyMetadata($book, $metadata);
        $this->applyStyles($book, $css);
        $this->applyResources($book, $resources);
        $this->addArticleChapter($book, $metadata['title'] ?? 'Artículo', $chapterXhtml);

        $book->finalize();

        return $book->getBook();
    }

    private function initBook(array $metadata): EPub
    {
        $lang = $metadata['language'] ?? 'es';
        return new EPub(EPub::BOOK_VERSION_EPUB3, $lang, EPub::DIRECTION_LEFT_TO_RIGHT);
    }

    private function applyMetadata(EPub $book, array $metadata): void
    {
        $book->setTitle($metadata['title'] ?? 'Artículo');
        $book->setLanguage($metadata['language'] ?? 'es');
        $book->setIdentifier($metadata['identifier'] ?? ('urn:uuid:' . uniqid()), EPub::IDENTIFIER_URI);

        if (!empty($metadata['authors']) && is_array($metadata['authors'])) {
            foreach ($metadata['authors'] as $author) {
                $book->setAuthor($author, $author);
            }
        }

        if (!empty($metadata['publisher'])) {
            $book->setPublisher($metadata['publisher'], '');
        }

        if (!empty($metadata['description'])) {
            $book->setDescription($metadata['description']);
        }
    }

    private function applyStyles(EPub $book, string $css): void
    {
        if (trim($css) !== '') {
            $book->addCSSFile('styles.css', 'css_epub', $css);
        }
    }

    private function applyResources(EPub $book, array $resources): void
    {
        foreach ($resources as $resource) {
            if (!empty($resource['data']) && !empty($resource['internalPath'])) {
                $fileId = 'img_' . md5($resource['internalPath']);
                $book->addFile($resource['internalPath'], $fileId, $resource['data'], $resource['mime']);
            }
        }
    }

    private function addArticleChapter(EPub $book, string $title, string $chapterXhtml): void
    {
        $book->addChapter($title, 'article.xhtml', $chapterXhtml, true);
    }
}
