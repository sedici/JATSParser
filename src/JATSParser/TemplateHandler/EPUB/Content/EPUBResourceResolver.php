<?php

namespace JATSParser\TemplateHandler\EPUB\Content;

use DOMDocument;
use DOMXPath;
use DOMElement;

class EPUBResourceResolver
{
    /**
     * Resuelve y colecciona las imágenes del artículo, reescribiendo los atributos src en el DOM
     */
    public function resolveImages(DOMDocument $dom, DOMXPath $xpath, $fileMgr, int $fileId): array
    {
        $imgNodes = $xpath->query('//img');
        if (!$imgNodes || $imgNodes->length === 0) {
            return [];
        }

        $resources = [];
        $index = 1;

        foreach ($imgNodes as $img) {
            if (!($img instanceof DOMElement)) {
                continue;
            }

            $resource = $this->processImageNode($img, $index, $fileMgr);
            if ($resource !== null) {
                $resources[] = $resource;
                $index++;
            }
        }

        return $resources;
    }

    /**
     * Procesa un nodo individual de imagen
     */
    private function processImageNode(DOMElement $img, int $index, $fileMgr): ?array
    {
        $rawSrc = trim($img->getAttribute('src'));
        if ($rawSrc === '') {
            return null;
        }

        $diskPath = $this->resolveDiskPath($rawSrc, $fileMgr);
        if (!$diskPath || !file_exists($diskPath)) {
            return null;
        }

        $mime = $this->detectMimeType($diskPath);
        $ext  = $this->detectExtension($diskPath, $mime);

        $internalPath = "images/image_{$index}.{$ext}";

        $img->setAttribute('src', $internalPath);

        return [
            'internalPath' => $internalPath,
            'diskPath'     => $diskPath,
            'mime'         => $mime,
            'data'         => file_get_contents($diskPath),
        ];
    }

    /**
     * Localiza la ruta absoluta del archivo en el sistema de archivos del servidor
     */
    private function resolveDiskPath(string $src, $fileMgr): ?string
    {
        error_log("[[[[SRC]]]: " . $src);
        if (file_exists($src)) {
            return $src;
        }

        if (str_starts_with($src, 'file://')) {
            $path = substr($src, 7);
            if (file_exists($path)) {
                return $path;
            }
        }

        if ($fileMgr) {
            $basePath = rtrim($fileMgr->getBasePath(), DIRECTORY_SEPARATOR);
            $fullPath = $basePath . DIRECTORY_SEPARATOR . ltrim($src, DIRECTORY_SEPARATOR);
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }

        return null;
    }

    private function detectMimeType(string $diskPath): string
    {
        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($diskPath);
            if ($mime) {
                return $mime;
            }
        }

        return 'image/png';
    }

    private function detectExtension(string $diskPath, string $mime): string
    {
        $ext = strtolower(pathinfo($diskPath, PATHINFO_EXTENSION));
        if ($ext !== '') {
            return $ext;
        }

        $map = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/svg+xml' => 'svg',
            'image/webp' => 'webp',
        ];

        return $map[$mime] ?? 'png';
    }
}
