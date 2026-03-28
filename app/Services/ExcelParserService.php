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

            $headers = array_map(fn($h) => $this->normalizeHeader($h), $headers);
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
            'specifications' => $this->parseSpecifications($data['specifications'] ?? null),
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

    private function parseSpecifications($value): ?array
    {
        if (empty($value)) return null;

        if (is_string($value) && (str_starts_with($value, '{') || str_starts_with($value, '['))) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $this->normalizeSpecifications($decoded);
            }
        }

        if (is_string($value) && str_contains($value, ':')) {
            $specs = [];
            foreach (explode(';', $value) as $pair) {
                $parts = explode(':', $pair, 2);
                if (count($parts) === 2) {
                    $specs[] = ['name' => trim($parts[0]), 'value' => trim($parts[1])];
                }
            }
            return !empty($specs) ? $specs : null;
        }

        return null;
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
     * Parse property-value pairs from "PropertyName:value1,value2; PropertyName2:value3" format.
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

            $values = array_map('trim', explode(',', $parts[1]));
            $values = array_values(array_filter($values, fn($v) => $v !== ''));

            if (!empty($values)) {
                $result[] = ['property' => $propertyName, 'values' => $values];
            }
        }

        return $result;
    }

    private function normalizeSpecifications(array $specs): array
    {
        $normalized = [];
        foreach ($specs as $key => $value) {
            if (is_array($value) && isset($value['name'], $value['value'])) {
                $normalized[] = $value;
            } else {
                $normalized[] = [
                    'name' => is_numeric($key) ? "Spec " . ($key + 1) : $key,
                    'value' => $value,
                ];
            }
        }
        return $normalized;
    }
}
