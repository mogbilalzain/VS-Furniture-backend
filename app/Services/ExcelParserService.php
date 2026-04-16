<?php

namespace App\Services;

use App\Models\Category;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ExcelParserService
{
    private array $categoryCache = [];

    /**
     * Parse Excel file and extract product data with validation.
     */
    public function parseExcelFile(string $filePath): array
    {
        try {
            $data = Excel::toArray([], $filePath);

            if (empty($data) || empty($data[0])) {
                throw new \Exception('Excel file is empty');
            }

            $rows = $data[0];
            $headers = array_shift($rows);

            $headers = array_filter($headers, fn($h) => $h !== null && $h !== '');
            $headers = array_values(array_map(fn($h) => $this->normalizeHeader($h), $headers));
            $this->validateHeaders($headers);
            $this->preloadCategories();

            $products = [];
            $rowNumber = 2;

            foreach ($rows as $row) {
                if ($this->isEmptyRow($row)) {
                    $rowNumber++;
                    continue;
                }

                try {
                    $products[] = $this->parseRow($headers, $row, $rowNumber);
                } catch (\Exception $e) {
                    $products[] = [
                        'row_number' => $rowNumber,
                        'error' => "Row {$rowNumber}: {$e->getMessage()}",
                        'raw_data' => $row,
                        'is_error' => true,
                    ];
                }

                $rowNumber++;
            }

            // Parse optional extra sheets and merge by SKU
            $sheetProperties = isset($data[1]) ? $this->parsePropertiesSheet($data[1]) : [];
            $sheetMaterials = isset($data[2]) ? $this->parseMaterialsSheet($data[2]) : [];
            $sheetCertifications = isset($data[3]) ? $this->parseCertificationsSheet($data[3]) : [];

            if (!empty($sheetProperties) || !empty($sheetMaterials) || !empty($sheetCertifications)) {
                $products = $this->mergeSheetDataIntoProducts($products, $sheetProperties, $sheetMaterials, $sheetCertifications);
            }

            return [
                'success' => true,
                'products' => $products,
                'total_rows' => count($products),
            ];
        } catch (\Exception $e) {
            Log::error('Excel parsing failed', ['error' => $e->getMessage(), 'file' => $filePath]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function preloadCategories(): void
    {
        $this->categoryCache = Category::all()->keyBy('id')->toArray();
    }

    /**
     * Resolve category from either category_id or category name.
     * Returns [id, name] or throws.
     */
    public function resolveCategory(array $data): array
    {
        if (!empty($data['category_id'])) {
            $id = (int) $data['category_id'];
            if (isset($this->categoryCache[$id])) {
                return ['id' => $id, 'name' => $this->categoryCache[$id]['name']];
            }
            throw new \Exception("Category ID '{$id}' does not exist");
        }

        $categoryName = trim($data['category'] ?? '');
        if (empty($categoryName)) {
            throw new \Exception('Category is required');
        }

        foreach ($this->categoryCache as $cat) {
            if (mb_strtolower($cat['name']) === mb_strtolower($categoryName)) {
                return ['id' => $cat['id'], 'name' => $cat['name']];
            }
        }

        throw new \Exception("Category '{$categoryName}' not found");
    }

    private function normalizeHeader(string $header): string
    {
        return str_replace([' ', '-'], '_', trim(strtolower($header)));
    }

    private function validateHeaders(array $headers): void
    {
        if (!in_array('product_name', $headers)) {
            throw new \Exception("Missing required column: 'product_name'");
        }

        $hasCategoryColumn = in_array('category', $headers) || in_array('category_id', $headers);
        if (!$hasCategoryColumn) {
            throw new \Exception("Missing required column: 'category' or 'category_id'");
        }
    }

    private function isEmptyRow(array $row): bool
    {
        return empty(array_filter($row, fn($cell) => $cell !== null && $cell !== ''));
    }

    private function parseRow(array $headers, array $row, int $rowNumber): array
    {
        // Pad row to match header count
        while (count($row) < count($headers)) {
            $row[] = null;
        }

        $data = array_combine($headers, array_slice($row, 0, count($headers)));

        $productName = trim($data['product_name'] ?? '');
        if (empty($productName)) {
            throw new \Exception('Product name is required');
        }

        $resolved = $this->resolveCategory($data);

        return [
            'row_number' => $rowNumber,
            'name' => $productName,
            'slug' => Str::slug($productName),
            'description' => $data['description'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'category' => $resolved['name'],
            'category_id' => $resolved['id'],
            'sku' => $data['sku'] ?? null,
            'model' => $data['model'] ?? null,
            'status' => $this->parseStatus($data['status'] ?? 'active'),
            'is_featured' => $this->parseBoolean($data['is_featured'] ?? false),
            'sort_order' => $this->parseInt($data['sort_order'] ?? 0),
            'materials' => $this->parseMaterialCodes($data['materials'] ?? null),
            'certifications' => $this->parseCertificationTitles($data['certifications'] ?? null),
            'properties' => $this->parseProperties($data['properties'] ?? null),
            'is_error' => false,
        ];
    }

    private function parseStatus($value): string
    {
        $value = strtolower(trim((string) $value));
        return in_array($value, ['inactive', 'disabled', 'hidden', 'no', '0']) ? 'inactive' : 'active';
    }

    private function parseBoolean($value): bool
    {
        if (is_bool($value)) return $value;
        $value = strtolower(trim((string) $value));
        return in_array($value, ['yes', 'true', '1', 'y', 't', 'نعم']);
    }

    private function parseInt($value): int
    {
        return (int) preg_replace('/[^0-9-]/', '', (string) $value);
    }

    /**
     * Parse comma-separated material codes into an array.
     * e.g. "M030, F010, L020" => ['M030', 'F010', 'L020']
     */
    private function parseMaterialCodes($value): array
    {
        if (empty($value)) return [];

        $codes = array_map('trim', explode(',', (string) $value));
        return array_values(array_filter($codes, fn($c) => $c !== ''));
    }

    /**
     * Parse comma-separated certification titles into an array.
     * e.g. "GS Tested Safety, GREENGUARD Gold" => ['GS Tested Safety', 'GREENGUARD Gold']
     */
    private function parseCertificationTitles($value): array
    {
        if (empty($value)) return [];

        $titles = array_map('trim', explode(',', (string) $value));
        return array_values(array_filter($titles, fn($t) => $t !== ''));
    }

    /**
     * Parse property-value pairs from two supported formats:
     *   "PropertyName:value1,value2; PropertyName2:value3"           (backward-compatible)
     *   "GroupName > PropertyName:value1,value2; PropertyName2:value3" (group-aware)
     *
     * When a "GroupName > " prefix is present it is stored separately so
     * resolveProperties can use it for disambiguation.
     */
    private function parseProperties($value): array
    {
        if (empty($value)) return [];

        $result = [];
        $entries = array_map('trim', explode(';', (string) $value));

        foreach ($entries as $entry) {
            if (empty($entry)) continue;
            $parts = explode(':', $entry, 2);
            if (count($parts) !== 2) continue;

            $propertyName = trim($parts[0]);
            if (empty($propertyName)) continue;

            $groupName = null;
            if (str_contains($propertyName, '>')) {
                $gpParts = explode('>', $propertyName, 2);
                $groupName = trim($gpParts[0]);
                $propertyName = trim($gpParts[1]);
                if (empty($propertyName)) continue;
            }

            $values = array_map('trim', explode(',', $parts[1]));
            $values = array_values(array_filter($values, fn($v) => $v !== ''));

            if (!empty($values)) {
                $item = ['property' => $propertyName, 'values' => $values];
                if ($groupName) {
                    $item['group'] = $groupName;
                }
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Parse Sheet 2 (Properties): sku | group | property | value
     * Returns array keyed by SKU, each containing an array of property entries.
     */
    private function parsePropertiesSheet(array $rows): array
    {
        if (empty($rows)) return [];

        $headers = array_map(fn($h) => $this->normalizeHeader((string) $h), array_shift($rows));

        $skuIdx = array_search('sku', $headers);
        $groupIdx = array_search('group', $headers);
        $propertyIdx = array_search('property', $headers);
        $valueIdx = array_search('value', $headers);

        if ($skuIdx === false || $propertyIdx === false || $valueIdx === false) {
            Log::warning('Properties sheet missing required columns (sku, property, value). Skipping.');
            return [];
        }

        $bySku = [];
        foreach ($rows as $row) {
            if ($this->isEmptyRow($row)) continue;

            $sku = trim((string) ($row[$skuIdx] ?? ''));
            $group = ($groupIdx !== false) ? trim((string) ($row[$groupIdx] ?? '')) : '';
            $property = trim((string) ($row[$propertyIdx] ?? ''));
            $value = trim((string) ($row[$valueIdx] ?? ''));

            if (empty($sku) || empty($property) || empty($value)) continue;

            if (!isset($bySku[$sku])) {
                $bySku[$sku] = [];
            }

            $found = false;
            foreach ($bySku[$sku] as &$existing) {
                $sameProperty = $existing['property'] === $property;
                $sameGroup = ($existing['group'] ?? '') === $group;
                if ($sameProperty && $sameGroup) {
                    $existing['values'][] = $value;
                    $found = true;
                    break;
                }
            }
            unset($existing);

            if (!$found) {
                $entry = ['property' => $property, 'values' => [$value]];
                if (!empty($group)) {
                    $entry['group'] = $group;
                }
                $bySku[$sku][] = $entry;
            }
        }

        return $bySku;
    }

    /**
     * Parse Sheet 3 (Materials): sku | material_code
     * Returns array keyed by SKU, each containing an array of material codes.
     */
    private function parseMaterialsSheet(array $rows): array
    {
        if (empty($rows)) return [];

        $headers = array_map(fn($h) => $this->normalizeHeader((string) $h), array_shift($rows));

        $skuIdx = array_search('sku', $headers);
        $codeIdx = array_search('material_code', $headers);

        if ($skuIdx === false || $codeIdx === false) {
            Log::warning('Materials sheet missing required columns (sku, material_code). Skipping.');
            return [];
        }

        $bySku = [];
        foreach ($rows as $row) {
            if ($this->isEmptyRow($row)) continue;

            $sku = trim((string) ($row[$skuIdx] ?? ''));
            $code = trim((string) ($row[$codeIdx] ?? ''));

            if (empty($sku) || empty($code)) continue;

            if (!isset($bySku[$sku])) {
                $bySku[$sku] = [];
            }

            if (!in_array($code, $bySku[$sku])) {
                $bySku[$sku][] = $code;
            }
        }

        return $bySku;
    }

    /**
     * Parse Sheet 4 (Certifications): sku | certification
     * Returns array keyed by SKU, each containing an array of certification titles.
     */
    private function parseCertificationsSheet(array $rows): array
    {
        if (empty($rows)) return [];

        $headers = array_map(fn($h) => $this->normalizeHeader((string) $h), array_shift($rows));

        $skuIdx = array_search('sku', $headers);
        $certIdx = array_search('certification', $headers);

        if ($skuIdx === false || $certIdx === false) {
            Log::warning('Certifications sheet missing required columns (sku, certification). Skipping.');
            return [];
        }

        $bySku = [];
        foreach ($rows as $row) {
            if ($this->isEmptyRow($row)) continue;

            $sku = trim((string) ($row[$skuIdx] ?? ''));
            $title = trim((string) ($row[$certIdx] ?? ''));

            if (empty($sku) || empty($title)) continue;

            if (!isset($bySku[$sku])) {
                $bySku[$sku] = [];
            }

            if (!in_array($title, $bySku[$sku])) {
                $bySku[$sku][] = $title;
            }
        }

        return $bySku;
    }

    /**
     * Merge data from extra sheets into the parsed products array by matching SKU.
     * Sheet data is appended to (not replacing) any inline column data.
     */
    private function mergeSheetDataIntoProducts(array $products, array $sheetProperties, array $sheetMaterials, array $sheetCertifications): array
    {
        foreach ($products as &$product) {
            if (!empty($product['is_error'])) continue;

            $sku = $product['sku'] ?? null;
            if (empty($sku)) continue;

            if (isset($sheetProperties[$sku])) {
                $product['properties'] = array_merge($product['properties'] ?? [], $sheetProperties[$sku]);
            }

            if (isset($sheetMaterials[$sku])) {
                $existing = $product['materials'] ?? [];
                $product['materials'] = array_values(array_unique(array_merge($existing, $sheetMaterials[$sku])));
            }

            if (isset($sheetCertifications[$sku])) {
                $existing = $product['certifications'] ?? [];
                $product['certifications'] = array_values(array_unique(array_merge($existing, $sheetCertifications[$sku])));
            }
        }
        unset($product);

        return $products;
    }
}
