<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ZipExtractorService
{
    const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    /**
     * Scan ZIP structure without extracting content.
     * Returns file metadata only (names, sizes) for the preview step.
     */
    public function scanStructure(string $zipPath): array
    {
        try {
            if (!class_exists(\ZipArchive::class)) {
                throw new \Exception('PHP zip extension is not installed. Restart your server or enable extension=zip in php.ini');
            }

            $zip = new \ZipArchive;

            if ($zip->open($zipPath) !== true) {
                throw new \Exception('Failed to open ZIP file');
            }

            $images = [];

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $filename = $stat['name'];

                if ($this->shouldSkipFile($filename)) {
                    continue;
                }

                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
                    continue;
                }

                $images[] = [
                    'index' => $i,
                    'original_name' => basename($filename),
                    'name_without_ext' => pathinfo(basename($filename), PATHINFO_FILENAME),
                    'extension' => $extension,
                    'size' => $stat['size'],
                ];
            }

            $zip->close();

            Log::info("Scanned ZIP structure: " . count($images) . " images found");

            return [
                'success' => true,
                'images' => $images,
                'total_images' => count($images),
            ];
        } catch (\Exception $e) {
            Log::error('ZIP scan failed', ['error' => $e->getMessage(), 'file' => $zipPath]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Extract a single image by its index inside the ZIP.
     * Used during import to stream images one-by-one.
     */
    public function extractSingleImage(string $zipPath, int $index): ?array
    {
        $zip = new \ZipArchive;

        if ($zip->open($zipPath) !== true) {
            return null;
        }

        $stat = $zip->statIndex($index);
        if (!$stat) {
            $zip->close();
            return null;
        }

        $content = $zip->getFromIndex($index);
        $zip->close();

        if ($content === false) {
            return null;
        }

        $filename = basename($stat['name']);

        return [
            'original_name' => $filename,
            'extension' => strtolower(pathinfo($filename, PATHINFO_EXTENSION)),
            'content' => $content,
            'size' => strlen($content),
        ];
    }

    /**
     * Match scanned image metadata to products by SKU first, then by name.
     *
     * @param array $products  Parsed product rows from Excel
     * @param array $images    Image metadata from scanStructure()
     * @return array  Keyed by product name, each value is an array of matched image metadata
     */
    public function matchImagesToProducts(array $products, array $images): array
    {
        $matches = [];

        foreach ($products as $product) {
            if (!empty($product['is_error'])) {
                continue;
            }

            $productName = $product['name'];
            $sku = $product['sku'] ?? null;

            $matched = $this->findMatchingImages($productName, $sku, $images);

            if (!empty($matched)) {
                $matches[$productName] = $matched;
            }
        }

        Log::info("Matched images for " . count($matches) . " products");

        return $matches;
    }

    private function findMatchingImages(string $productName, ?string $sku, array $images): array
    {
        $matchedImages = [];

        foreach ($images as $image) {
            $imageBaseName = $image['name_without_ext'];

            $matchType = $this->getMatchType($imageBaseName, $productName, $sku);

            if (!$matchType) {
                continue;
            }

            $suffix = $this->extractSuffix($imageBaseName, $matchType === 'sku' ? $sku : $productName);
            $isPrimary = false;
            $sortOrder = 999;

            if (empty($suffix) || in_array($suffix, ['-', '_'])) {
                $isPrimary = true;
                $sortOrder = 0;
            } elseif (preg_match('/[-_](\d+)$/', $suffix, $m)) {
                $sortOrder = (int) $m[1];
            }

            $matchedImages[] = array_merge($image, [
                'is_primary' => $isPrimary,
                'sort_order' => $sortOrder,
                'match_type' => $matchType,
            ]);
        }

        usort($matchedImages, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);

        if (!empty($matchedImages) && !$matchedImages[0]['is_primary']) {
            $matchedImages[0]['is_primary'] = true;
            $matchedImages[0]['sort_order'] = 0;
        }

        return $matchedImages;
    }

    /**
     * Determine if an image name matches a product by SKU or name.
     */
    private function getMatchType(string $imageBaseName, string $productName, ?string $sku): ?string
    {
        if ($sku && $this->nameStartsWith($imageBaseName, $sku)) {
            return 'sku';
        }

        if ($this->nameStartsWith($this->normalizeString($imageBaseName), $this->normalizeString($productName))) {
            return 'name';
        }

        return null;
    }

    private function extractSuffix(string $imageBaseName, string $matchKey): string
    {
        $normalizedImage = $this->normalizeString($imageBaseName);
        $normalizedKey = $this->normalizeString($matchKey);
        return substr($normalizedImage, strlen($normalizedKey));
    }

    private function shouldSkipFile(string $filename): bool
    {
        return str_ends_with($filename, '/')
            || str_contains($filename, '__MACOSX')
            || str_starts_with(basename($filename), '.');
    }

    private function normalizeString(string $str): string
    {
        $str = mb_strtolower($str, 'UTF-8');
        $str = preg_replace('/[\s_-]+/', '-', $str);
        $str = preg_replace('/[^a-z0-9\x{0600}-\x{06FF}-]/u', '', $str);
        return trim($str, '-');
    }

    private function nameStartsWith(string $haystack, string $needle): bool
    {
        return mb_strpos($haystack, $needle, 0, 'UTF-8') === 0;
    }

    /**
     * Legacy method kept for backward compatibility.
     * Extracts all images at once. Prefer scanStructure() + extractSingleImage().
     */
    public function extractImages(string $zipPath): array
    {
        $scanResult = $this->scanStructure($zipPath);

        if (!$scanResult['success']) {
            return $scanResult;
        }

        $zip = new \ZipArchive;
        if ($zip->open($zipPath) !== true) {
            return ['success' => false, 'error' => 'Failed to open ZIP file'];
        }

        $images = [];
        foreach ($scanResult['images'] as $meta) {
            $content = $zip->getFromIndex($meta['index']);
            if ($content !== false) {
                $images[] = array_merge($meta, ['content' => $content]);
            }
        }

        $zip->close();

        return [
            'success' => true,
            'images' => $images,
            'total_images' => count($images),
        ];
    }
}
