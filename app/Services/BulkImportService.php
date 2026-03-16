<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Category;
use App\Models\ImportLog;
use App\Models\ImportDetail;
use App\Helpers\ImageHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class BulkImportService
{
    protected ExcelParserService $excelParser;
    protected ZipExtractorService $zipExtractor;

    public function __construct(ExcelParserService $excelParser, ZipExtractorService $zipExtractor)
    {
        $this->excelParser = $excelParser;
        $this->zipExtractor = $zipExtractor;
    }

    /**
     * Validate and preview: parse Excel, scan ZIP, return preview data.
     * Files are stored under a session ID so they can be reused during import.
     */
    public function validateAndPreview(string $excelPath, ?string $zipPath, int $userId): array
    {
        $sessionId = Str::uuid()->toString();
        $sessionDir = storage_path("app/imports/{$sessionId}");

        if (!is_dir($sessionDir)) {
            mkdir($sessionDir, 0755, true);
        }

        if (!file_exists($excelPath)) {
            return ['success' => false, 'error' => 'Excel file not found at: ' . $excelPath];
        }

        // Move files to session directory (move instead of copy to avoid issues)
        $sessionExcel = $sessionDir . '/' . basename($excelPath);
        rename($excelPath, $sessionExcel);

        $sessionZip = null;
        if ($zipPath && file_exists($zipPath)) {
            $sessionZip = $sessionDir . '/' . basename($zipPath);
            rename($zipPath, $sessionZip);
        }

        // Parse Excel
        $excelResult = $this->excelParser->parseExcelFile($sessionExcel);
        if (!$excelResult['success']) {
            $this->cleanupSession($sessionDir);
            return ['success' => false, 'error' => 'Excel parsing failed: ' . $excelResult['error']];
        }

        $products = $excelResult['products'];

        // Scan ZIP (metadata only)
        $imageList = [];
        $matchedImages = [];
        if ($sessionZip) {
            $zipResult = $this->zipExtractor->scanStructure($sessionZip);
            if ($zipResult['success']) {
                $imageList = $zipResult['images'];
                $matchedImages = $this->zipExtractor->matchImagesToProducts($products, $imageList);
            }
        }

        // Build preview rows
        $preview = [];
        $validCount = 0;
        $errorCount = 0;

        foreach ($products as $p) {
            if (!empty($p['is_error'])) {
                $errorCount++;
                $preview[] = [
                    'row' => $p['row_number'],
                    'name' => $p['raw_data'][0] ?? 'Unknown',
                    'sku' => null,
                    'category' => null,
                    'status' => 'error',
                    'images_count' => 0,
                    'images' => [],
                    'error' => $p['error'],
                    'selected' => false,
                ];
                continue;
            }

            $images = $matchedImages[$p['name']] ?? [];
            $validCount++;

            $preview[] = [
                'row' => $p['row_number'],
                'name' => $p['name'],
                'sku' => $p['sku'],
                'category' => $p['category'],
                'category_id' => $p['category_id'],
                'status' => 'valid',
                'images_count' => count($images),
                'images' => array_map(fn($img) => [
                    'name' => $img['original_name'],
                    'is_primary' => $img['is_primary'],
                    'match_type' => $img['match_type'] ?? 'name',
                ], $images),
                'error' => null,
                'selected' => true,
            ];
        }

        // Save session metadata
        file_put_contents($sessionDir . '/meta.json', json_encode([
            'user_id' => $userId,
            'excel_file' => basename($sessionExcel),
            'zip_file' => $sessionZip ? basename($sessionZip) : null,
            'created_at' => now()->toISOString(),
        ]));

        return [
            'success' => true,
            'session_id' => $sessionId,
            'total_rows' => count($products),
            'valid_rows' => $validCount,
            'error_rows' => $errorCount,
            'total_images' => count($imageList),
            'preview' => $preview,
        ];
    }

    /**
     * Execute import using a previously validated session.
     *
     * @param string $sessionId
     * @param array $selectedRows  Row numbers the user chose to import
     * @param int $userId
     */
    public function executeImport(string $sessionId, array $selectedRows, int $userId): array
    {
        $sessionDir = storage_path("app/imports/{$sessionId}");

        if (!file_exists($sessionDir . '/meta.json')) {
            return ['success' => false, 'error' => 'Invalid or expired import session'];
        }

        $meta = json_decode(file_get_contents($sessionDir . '/meta.json'), true);
        $excelPath = $sessionDir . '/' . $meta['excel_file'];
        $zipPath = $meta['zip_file'] ? ($sessionDir . '/' . $meta['zip_file']) : null;

        $startTime = now();

        $importLog = ImportLog::create([
            'user_id' => $userId,
            'excel_file_name' => $meta['excel_file'],
            'zip_file_name' => $meta['zip_file'],
            'status' => 'processing',
            'started_at' => $startTime,
        ]);

        try {
            // Re-parse Excel
            $excelResult = $this->excelParser->parseExcelFile($excelPath);
            if (!$excelResult['success']) {
                throw new \Exception('Excel parsing failed: ' . $excelResult['error']);
            }

            $products = $excelResult['products'];
            $importLog->update(['total_rows' => count($products)]);

            // Scan ZIP for matching
            $matchedImages = [];
            if ($zipPath) {
                $zipScan = $this->zipExtractor->scanStructure($zipPath);
                if ($zipScan['success']) {
                    $matchedImages = $this->zipExtractor->matchImagesToProducts($products, $zipScan['images']);
                }
            }

            // Import selected products
            $results = $this->importProducts($products, $matchedImages, $selectedRows, $zipPath, $importLog->id);

            $endTime = now();
            $importLog->update([
                'successful_imports' => $results['successful'],
                'failed_imports' => $results['failed'],
                'skipped_imports' => $results['skipped'],
                'status' => 'completed',
                'completed_at' => $endTime,
                'processing_time_seconds' => $endTime->diffInSeconds($startTime),
            ]);

            return [
                'success' => true,
                'import_log_id' => $importLog->id,
                'total_rows' => count($products),
                'successful' => $results['successful'],
                'failed' => $results['failed'],
                'skipped' => $results['skipped'],
                'processing_time' => $endTime->diffInSeconds($startTime),
                'details' => $results['details'],
            ];
        } catch (\Exception $e) {
            Log::error('Bulk import failed', ['error' => $e->getMessage(), 'import_log_id' => $importLog->id]);

            $importLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            return ['success' => false, 'error' => $e->getMessage(), 'import_log_id' => $importLog->id];
        } finally {
            $this->cleanupSession($sessionDir);
        }
    }

    /**
     * Legacy single-call import (validate + import in one request).
     */
    public function processBulkImport(string $excelPath, string $zipPath, int $userId): array
    {
        $startTime = now();

        $importLog = ImportLog::create([
            'user_id' => $userId,
            'excel_file_name' => basename($excelPath),
            'zip_file_name' => basename($zipPath),
            'status' => 'processing',
            'started_at' => $startTime,
        ]);

        try {
            $excelResult = $this->excelParser->parseExcelFile($excelPath);
            if (!$excelResult['success']) {
                throw new \Exception('Excel parsing failed: ' . $excelResult['error']);
            }

            $products = $excelResult['products'];
            $importLog->update(['total_rows' => count($products)]);

            $zipScan = $this->zipExtractor->scanStructure($zipPath);
            if (!$zipScan['success']) {
                throw new \Exception('ZIP scan failed: ' . $zipScan['error']);
            }

            $matchedImages = $this->zipExtractor->matchImagesToProducts($products, $zipScan['images']);
            $allRows = array_map(fn($p) => $p['row_number'], $products);
            $results = $this->importProducts($products, $matchedImages, $allRows, $zipPath, $importLog->id);

            $endTime = now();
            $importLog->update([
                'successful_imports' => $results['successful'],
                'failed_imports' => $results['failed'],
                'skipped_imports' => $results['skipped'],
                'status' => 'completed',
                'completed_at' => $endTime,
                'processing_time_seconds' => $endTime->diffInSeconds($startTime),
            ]);

            return [
                'success' => true,
                'import_log_id' => $importLog->id,
                'total_rows' => count($products),
                'successful' => $results['successful'],
                'failed' => $results['failed'],
                'skipped' => $results['skipped'],
                'processing_time' => $endTime->diffInSeconds($startTime),
                'details' => $results['details'],
            ];
        } catch (\Exception $e) {
            Log::error('Bulk import failed', ['error' => $e->getMessage(), 'import_log_id' => $importLog->id]);

            $importLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            return ['success' => false, 'error' => $e->getMessage(), 'import_log_id' => $importLog->id];
        } finally {
            $this->cleanupTempFiles([$excelPath, $zipPath]);
        }
    }

    private function importProducts(array $products, array $matchedImages, array $selectedRows, ?string $zipPath, int $importLogId): array
    {
        $successful = 0;
        $failed = 0;
        $skipped = 0;
        $details = [];

        foreach ($products as $productData) {
            $rowNumber = $productData['row_number'];

            // Skip rows not selected by user
            if (!in_array($rowNumber, $selectedRows)) {
                $skipped++;
                $details[] = [
                    'row' => $rowNumber,
                    'product_name' => $productData['name'] ?? 'Unknown',
                    'status' => 'skipped',
                    'error' => 'Not selected for import',
                ];
                continue;
            }

            // Skip rows with parse errors
            if (!empty($productData['is_error'])) {
                $this->createImportDetail($importLogId, $rowNumber, $productData['raw_data'][0] ?? 'Unknown', null, 'skipped', $productData['error']);
                $skipped++;
                $details[] = [
                    'row' => $rowNumber,
                    'product_name' => $productData['raw_data'][0] ?? 'Unknown',
                    'status' => 'skipped',
                    'error' => $productData['error'],
                ];
                continue;
            }

            DB::beginTransaction();
            try {
                $categoryId = $productData['category_id'] ?? null;

                if (!$categoryId) {
                    $category = Category::where('name', $productData['category'])->first();
                    if (!$category) {
                        throw new \Exception("Category '{$productData['category']}' not found");
                    }
                    $categoryId = $category->id;
                }

                if ($productData['sku']) {
                    $existing = Product::where('sku', $productData['sku'])->first();
                    if ($existing) {
                        throw new \Exception("SKU '{$productData['sku']}' already exists");
                    }
                }

                $slug = $productData['slug'];
                $counter = 1;
                while (Product::where('slug', $slug)->exists()) {
                    $slug = $productData['slug'] . '-' . $counter++;
                }

                $product = Product::create([
                    'name' => $productData['name'],
                    'slug' => $slug,
                    'description' => $productData['description'],
                    'short_description' => $productData['short_description'],
                    'category_id' => $categoryId,
                    'sku' => $productData['sku'],
                    'model' => $productData['model'],
                    'status' => $productData['status'],
                    'is_featured' => $productData['is_featured'],
                    'sort_order' => $productData['sort_order'],
                    'specifications' => $productData['specifications'] ? json_encode($productData['specifications']) : null,
                ]);

                // Stream images one-by-one from ZIP
                $imagesUploaded = 0;
                $productImages = $matchedImages[$productData['name']] ?? [];

                if (!empty($productImages) && $zipPath) {
                    $imagesUploaded = $this->uploadProductImagesStreamed($product, $productImages, $zipPath);
                }

                $this->createImportDetail(
                    $importLogId, $rowNumber, $productData['name'], $productData['sku'],
                    'success', null, $product->id, $imagesUploaded,
                    array_map(fn($img) => $img['original_name'], $productImages)
                );

                $successful++;
                $details[] = [
                    'row' => $rowNumber,
                    'product_name' => $productData['name'],
                    'product_id' => $product->id,
                    'status' => 'success',
                    'images_count' => $imagesUploaded,
                ];

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Product import failed', ['product' => $productData['name'] ?? 'Unknown', 'error' => $e->getMessage()]);

                $this->createImportDetail($importLogId, $rowNumber, $productData['name'] ?? 'Unknown', $productData['sku'] ?? null, 'failed', $e->getMessage());

                $failed++;
                $details[] = [
                    'row' => $rowNumber,
                    'product_name' => $productData['name'] ?? 'Unknown',
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return compact('successful', 'failed', 'skipped', 'details');
    }

    /**
     * Upload images by extracting them one-by-one from the ZIP.
     */
    private function uploadProductImagesStreamed(Product $product, array $imagesMeta, string $zipPath): int
    {
        $uploaded = 0;

        foreach ($imagesMeta as $meta) {
            try {
                if (!isset($meta['index'])) {
                    continue;
                }

                $imageData = $this->zipExtractor->extractSingleImage($zipPath, $meta['index']);
                if (!$imageData) {
                    continue;
                }

                $tempPath = storage_path('app/temp/' . Str::random(10) . '.' . $imageData['extension']);
                if (!file_exists(dirname($tempPath))) {
                    mkdir(dirname($tempPath), 0755, true);
                }

                file_put_contents($tempPath, $imageData['content']);
                unset($imageData['content']);

                $uploadedFile = new UploadedFile($tempPath, $imageData['original_name'], mime_content_type($tempPath), null, true);
                $uploadResult = ImageHelper::uploadImage($uploadedFile, 'products', (string) $product->id);

                if ($uploadResult['success']) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $uploadResult['data']['path'],
                        'is_primary' => $meta['is_primary'],
                        'is_featured' => $meta['is_primary'],
                        'is_active' => true,
                        'sort_order' => $meta['sort_order'],
                    ]);
                    $uploaded++;
                }

                if (file_exists($tempPath)) {
                    unlink($tempPath);
                }
            } catch (\Exception $e) {
                Log::error('Image upload failed', ['product_id' => $product->id, 'image' => $meta['original_name'] ?? '?', 'error' => $e->getMessage()]);
            }
        }

        return $uploaded;
    }

    private function createImportDetail(
        int $importLogId, int $rowNumber, string $productName, ?string $sku,
        string $status, ?string $errorMessage = null, ?int $productId = null,
        int $imagesUploaded = 0, ?array $matchedImages = null
    ): void {
        ImportDetail::create([
            'import_log_id' => $importLogId,
            'row_number' => $rowNumber,
            'product_name' => $productName,
            'sku' => $sku,
            'status' => $status,
            'error_message' => $errorMessage,
            'product_id' => $productId,
            'images_uploaded' => $imagesUploaded,
            'matched_images' => $matchedImages ? json_encode($matchedImages) : null,
        ]);
    }

    private function cleanupSession(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = glob($dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) unlink($file);
        }
        rmdir($dir);
        Log::info("Cleaned up import session: {$dir}");
    }

    private function cleanupTempFiles(array $filePaths): void
    {
        foreach ($filePaths as $path) {
            if ($path && file_exists($path)) {
                try {
                    unlink($path);
                } catch (\Exception $e) {
                    Log::warning("Failed to clean up temp file: {$path}");
                }
            }
        }
    }
}
