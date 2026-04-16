<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductMaterial;
use App\Models\Material;
use App\Models\Certification;
use App\Models\CategoryProperty;
use App\Models\PropertyGroup;
use App\Models\ProductPropertyValue;
use App\Models\PropertyValue;
use App\Models\ProductFile;
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
        $pdfList = [];
        $matchedImages = [];
        $matchedPdfs = [];
        if ($sessionZip) {
            $zipResult = $this->zipExtractor->scanStructure($sessionZip);
            if ($zipResult['success']) {
                $imageList = $zipResult['images'];
                $matchedImages = $this->zipExtractor->matchImagesToProducts($products, $imageList);
                $pdfList = $zipResult['pdf_files'] ?? [];
                if (!empty($pdfList)) {
                    $matchedPdfs = $this->zipExtractor->matchPdfsToProducts($products, $pdfList);
                }
            }
        }

        // Pre-load materials keyed by code for resolution
        $materialsByCode = Material::all()->keyBy('code');

        // Pre-load certifications keyed by lowercase title for resolution
        $certsByTitle = Certification::all()->keyBy(fn($c) => mb_strtolower($c->title));

        // Pre-load category properties with values for resolution
        $propsLookup = $this->buildPropertiesLookup();

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
                    'materials_count' => 0,
                    'materials' => [],
                    'certifications_count' => 0,
                    'certifications' => [],
                    'properties_count' => 0,
                    'properties' => [],
                    'files_count' => 0,
                    'files' => [],
                    'error' => $p['error'],
                    'selected' => false,
                ];
                continue;
            }

            $images = $matchedImages[$p['name']] ?? [];

            // Resolve material codes for preview
            $materialCodes = $p['materials'] ?? [];
            $resolvedMaterials = [];
            $materialWarnings = [];
            foreach ($materialCodes as $code) {
                if ($materialsByCode->has($code)) {
                    $mat = $materialsByCode->get($code);
                    $resolvedMaterials[] = ['code' => $code, 'name' => $mat->name];
                } else {
                    $materialWarnings[] = "Material code '{$code}' not found";
                }
            }

            // Resolve certification titles for preview
            $certTitles = $p['certifications'] ?? [];
            $resolvedCerts = [];
            $certWarnings = [];
            foreach ($certTitles as $title) {
                $key = mb_strtolower($title);
                if ($certsByTitle->has($key)) {
                    $cert = $certsByTitle->get($key);
                    $resolvedCerts[] = ['id' => $cert->id, 'title' => $cert->title];
                } else {
                    $certWarnings[] = "Certification '{$title}' not found";
                }
            }

            // Resolve properties for preview
            $parsedProps = $p['properties'] ?? [];
            [$resolvedProps, $propWarnings] = $this->resolveProperties($parsedProps, $p['category_id'], $propsLookup, false);

            // Matched PDFs for preview
            $pdfs = $matchedPdfs[$p['name']] ?? [];

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
                'materials_count' => count($resolvedMaterials),
                'materials' => $resolvedMaterials,
                'materials_warnings' => $materialWarnings,
                'certifications_count' => count($resolvedCerts),
                'certifications' => $resolvedCerts,
                'certifications_warnings' => $certWarnings,
                'properties_count' => count($resolvedProps),
                'properties' => $resolvedProps,
                'properties_warnings' => $propWarnings,
                'files_count' => count($pdfs),
                'files' => array_map(fn($f) => [
                    'name' => $f['original_name'],
                    'category' => $f['detected_category'],
                    'match_type' => $f['match_type'] ?? 'name',
                ], $pdfs),
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
            $matchedPdfs = [];
            if ($zipPath) {
                $zipScan = $this->zipExtractor->scanStructure($zipPath);
                if ($zipScan['success']) {
                    $matchedImages = $this->zipExtractor->matchImagesToProducts($products, $zipScan['images']);
                    $pdfList = $zipScan['pdf_files'] ?? [];
                    if (!empty($pdfList)) {
                        $matchedPdfs = $this->zipExtractor->matchPdfsToProducts($products, $pdfList);
                    }
                }
            }

            // Import selected products
            $results = $this->importProducts($products, $matchedImages, $matchedPdfs, $selectedRows, $zipPath, $importLog->id);

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
            $matchedPdfs = [];
            $pdfList = $zipScan['pdf_files'] ?? [];
            if (!empty($pdfList)) {
                $matchedPdfs = $this->zipExtractor->matchPdfsToProducts($products, $pdfList);
            }
            $allRows = array_map(fn($p) => $p['row_number'], $products);
            $results = $this->importProducts($products, $matchedImages, $matchedPdfs, $allRows, $zipPath, $importLog->id);

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

    private function importProducts(array $products, array $matchedImages, array $matchedPdfs, array $selectedRows, ?string $zipPath, int $importLogId): array
    {
        $successful = 0;
        $failed = 0;
        $skipped = 0;
        $details = [];

        // Pre-load all materials keyed by code (single query)
        $materialsByCode = Material::all()->keyBy('code');

        // Pre-load all certifications keyed by lowercase title (single query)
        $certsByTitle = Certification::all()->keyBy(fn($c) => mb_strtolower($c->title));

        // Pre-load category properties with values
        $propsLookup = $this->buildPropertiesLookup();

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
                ]);

                // Stream images one-by-one from ZIP
                $imagesUploaded = 0;
                $productImages = $matchedImages[$productData['name']] ?? [];

                if (!empty($productImages) && $zipPath) {
                    $imagesUploaded = $this->uploadProductImagesStreamed($product, $productImages, $zipPath);
                }

                // Assign materials
                $materialsAssigned = 0;
                $materialCodes = $productData['materials'] ?? [];
                foreach ($materialCodes as $index => $code) {
                    if ($materialsByCode->has($code)) {
                        $material = $materialsByCode->get($code);
                        $isDefault = ($index === 0);
                        ProductMaterial::assignMaterial($product->id, $material->id, $isDefault, $index);
                        $materialsAssigned++;
                    }
                }

                // Assign certifications
                $certificationsAssigned = 0;
                $certTitles = $productData['certifications'] ?? [];
                $certIds = [];
                foreach ($certTitles as $title) {
                    $key = mb_strtolower($title);
                    if ($certsByTitle->has($key)) {
                        $certIds[] = $certsByTitle->get($key)->id;
                        $certificationsAssigned++;
                    }
                }
                if (!empty($certIds)) {
                    $product->certifications()->attach($certIds);
                }

                // Assign properties
                $propertiesAssigned = 0;
                $parsedProps = $productData['properties'] ?? [];
                if (!empty($parsedProps)) {
                    [$resolvedProps, ] = $this->resolveProperties($parsedProps, $categoryId, $propsLookup, true);
                    $propValueIds = array_unique(array_filter(array_map(fn($r) => $r['id'], $resolvedProps)));
                    if (!empty($propValueIds)) {
                        ProductPropertyValue::syncProductProperties($product->id, array_values($propValueIds));
                        $propertiesAssigned = count($propValueIds);
                    }
                }

                // Upload matched PDF files
                $filesAssigned = 0;
                $productPdfs = $matchedPdfs[$productData['name']] ?? [];
                if (!empty($productPdfs) && $zipPath) {
                    $filesAssigned = $this->uploadProductPdfsStreamed($product, $productPdfs, $zipPath);
                }

                $this->createImportDetail(
                    $importLogId, $rowNumber, $productData['name'], $productData['sku'],
                    'success', null, $product->id, $imagesUploaded,
                    array_map(fn($img) => $img['original_name'], $productImages),
                    $materialsAssigned, $certificationsAssigned, $propertiesAssigned,
                    $filesAssigned
                );

                $successful++;
                $details[] = [
                    'row' => $rowNumber,
                    'product_name' => $productData['name'],
                    'product_id' => $product->id,
                    'status' => 'success',
                    'images_count' => $imagesUploaded,
                    'materials_count' => $materialsAssigned,
                    'certifications_count' => $certificationsAssigned,
                    'properties_count' => $propertiesAssigned,
                    'files_count' => $filesAssigned,
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
                        'image_url' => $uploadResult['data']['url'],
                        'alt_text' => $product->name,
                        'title' => $product->name,
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

    /**
     * Upload PDFs by extracting them one-by-one from the ZIP and creating ProductFile records.
     */
    private function uploadProductPdfsStreamed(Product $product, array $pdfsMeta, string $zipPath): int
    {
        $uploaded = 0;

        foreach ($pdfsMeta as $sortOrder => $meta) {
            try {
                if (!isset($meta['index'])) {
                    continue;
                }

                $pdfData = $this->zipExtractor->extractSinglePdf($zipPath, $meta['index']);
                if (!$pdfData) {
                    continue;
                }

                $filename = time() . '_' . Str::random(10) . '.pdf';
                $storagePath = "products/{$product->id}/files";
                $fullDir = storage_path("app/public/{$storagePath}");

                if (!is_dir($fullDir)) {
                    mkdir($fullDir, 0755, true);
                }

                file_put_contents("{$fullDir}/{$filename}", $pdfData['content']);
                unset($pdfData['content']);

                $displayName = pathinfo($meta['original_name'], PATHINFO_FILENAME);
                $category = $meta['detected_category'] ?? 'other';

                ProductFile::create([
                    'product_id' => $product->id,
                    'file_name' => $meta['original_name'],
                    'file_path' => "{$storagePath}/{$filename}",
                    'file_size' => $pdfData['size'],
                    'file_type' => 'application/pdf',
                    'mime_type' => 'application/pdf',
                    'display_name' => $displayName,
                    'file_category' => $category,
                    'is_active' => true,
                    'is_featured' => false,
                    'sort_order' => $sortOrder,
                    'download_count' => 0,
                    'metadata' => json_encode([
                        'original_name' => $meta['original_name'],
                        'imported_via' => 'bulk_import',
                    ]),
                ]);

                $uploaded++;
            } catch (\Exception $e) {
                Log::error('PDF upload failed', [
                    'product_id' => $product->id,
                    'pdf' => $meta['original_name'] ?? '?',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $uploaded;
    }

    private function createImportDetail(
        int $importLogId, int $rowNumber, string $productName, ?string $sku,
        string $status, ?string $errorMessage = null, ?int $productId = null,
        int $imagesUploaded = 0, ?array $matchedImages = null,
        int $materialsAssigned = 0, int $certificationsAssigned = 0,
        int $propertiesAssigned = 0, int $filesAssigned = 0
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
            'materials_assigned' => $materialsAssigned,
            'certifications_assigned' => $certificationsAssigned,
            'properties_assigned' => $propertiesAssigned,
            'files_assigned' => $filesAssigned,
        ]);
    }

    /**
     * Build a lookup of category properties with their values, grouped by category ID.
     * Each property is indexed by both its name and display_name (lowercase).
     * Each value is indexed by both its value and display_name (lowercase).
     */
    private function buildPropertiesLookup(): array
    {
        $lookup = [];
        $properties = CategoryProperty::with(['propertyValues', 'propertyGroup'])->get();

        foreach ($properties as $prop) {
            $catId = $prop->category_id;

            if (!isset($lookup[$catId])) {
                $lookup[$catId] = [];
            }

            $groupName = $prop->propertyGroup ? mb_strtolower($prop->propertyGroup->name) : null;
            $groupDisplayName = $prop->propertyGroup ? mb_strtolower($prop->propertyGroup->display_name) : null;

            $propEntry = [
                'name' => $prop->name,
                'group_name' => $groupName,
                'group_display_name' => $groupDisplayName,
                'values' => [],
            ];

            foreach ($prop->propertyValues as $pv) {
                $propEntry['values'][mb_strtolower($pv->value)] = $pv;
                if ($pv->display_name && mb_strtolower($pv->display_name) !== mb_strtolower($pv->value)) {
                    $propEntry['values'][mb_strtolower($pv->display_name)] = $pv;
                }
            }

            $nameKey = mb_strtolower($prop->name);
            $lookup[$catId][$nameKey] = $propEntry;

            if ($prop->display_name && mb_strtolower($prop->display_name) !== $nameKey) {
                $lookup[$catId][mb_strtolower($prop->display_name)] = $propEntry;
            }
        }

        return $lookup;
    }

    /**
     * Resolve parsed properties against the product's category.
     * Returns [resolved (array of PropertyValue IDs + display info), warnings].
     */
    private function resolveProperties(array $parsedProperties, int $categoryId, array $propsLookup, bool $autoCreate = false): array
    {
        $resolved = [];
        $warnings = [];
        $categoryProps = $propsLookup[$categoryId] ?? [];

        foreach ($parsedProperties as $entry) {
            $propKey = mb_strtolower($entry['property']);
            $groupName = $entry['group'] ?? null;
            $groupFilter = $groupName ? mb_strtolower($groupName) : null;

            if (!isset($categoryProps[$propKey])) {
                if ($autoCreate) {
                    $propertyGroupId = null;
                    if ($groupName) {
                        $group = PropertyGroup::firstOrCreate(
                            ['category_id' => $categoryId, 'name' => $groupName],
                            ['display_name' => $groupName, 'is_active' => true]
                        );
                        $propertyGroupId = $group->id;
                    }

                    $property = CategoryProperty::firstOrCreate(
                        ['category_id' => $categoryId, 'name' => $entry['property']],
                        [
                            'display_name' => $entry['property'],
                            'property_group_id' => $propertyGroupId,
                            'input_type' => 'select',
                            'is_active' => true,
                            'is_filterable' => true,
                        ]
                    );

                    foreach ($entry['values'] as $val) {
                        $pv = PropertyValue::firstOrCreate(
                            ['category_property_id' => $property->id, 'value' => $val],
                            ['display_name' => $val, 'is_active' => true]
                        );
                        $resolved[] = [
                            'id' => $pv->id,
                            'property' => $property->name,
                            'value' => $pv->value,
                            'display_name' => $pv->display_name,
                            'auto_created' => true,
                        ];
                    }

                    $warnings[] = "Property '{$entry['property']}'" . ($groupName ? " under group '{$groupName}'" : "") . " was auto-created";
                } else {
                    foreach ($entry['values'] as $val) {
                        $resolved[] = [
                            'id' => null,
                            'property' => $entry['property'],
                            'value' => $val,
                            'display_name' => $val,
                            'auto_created' => true,
                        ];
                    }
                    $warnings[] = "Property '{$entry['property']}'" . ($groupName ? " under group '{$groupName}'" : "") . " will be auto-created";
                }
                continue;
            }

            $propData = $categoryProps[$propKey];

            if ($groupFilter) {
                $matchesGroup = (
                    $propData['group_name'] === $groupFilter ||
                    $propData['group_display_name'] === $groupFilter
                );
                if (!$matchesGroup) {
                    $warnings[] = "Property '{$entry['property']}' does not belong to group '{$entry['group']}'";
                    continue;
                }
            }

            foreach ($entry['values'] as $val) {
                $valKey = mb_strtolower($val);
                if (isset($propData['values'][$valKey])) {
                    $pv = $propData['values'][$valKey];
                    $resolved[] = [
                        'id' => $pv->id,
                        'property' => $propData['name'],
                        'value' => $pv->value,
                        'display_name' => $pv->display_name,
                    ];
                } else {
                    if ($autoCreate) {
                        $property = CategoryProperty::where('category_id', $categoryId)
                            ->where(function ($q) use ($propKey) {
                                $q->whereRaw('LOWER(name) = ?', [$propKey])
                                  ->orWhereRaw('LOWER(display_name) = ?', [$propKey]);
                            })->first();

                        if ($property) {
                            $pv = PropertyValue::firstOrCreate(
                                ['category_property_id' => $property->id, 'value' => $val],
                                ['display_name' => $val, 'is_active' => true]
                            );
                            $resolved[] = [
                                'id' => $pv->id,
                                'property' => $property->name,
                                'value' => $pv->value,
                                'display_name' => $pv->display_name,
                                'auto_created' => true,
                            ];
                            $warnings[] = "Value '{$val}' for property '{$entry['property']}' was auto-created";
                        }
                    } else {
                        $resolved[] = [
                            'id' => null,
                            'property' => $entry['property'],
                            'value' => $val,
                            'display_name' => $val,
                            'auto_created' => true,
                        ];
                        $warnings[] = "Value '{$val}' for property '{$entry['property']}' will be auto-created";
                    }
                }
            }
        }

        return [$resolved, $warnings];
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
