<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ExcelParserService;
use Maatwebsite\Excel\Facades\Excel;

class ExcelParserServiceTest extends TestCase
{
    protected ExcelParserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExcelParserService();
    }

    public function test_parse_valid_excel_data(): void
    {
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([[
                ['product_name', 'description', 'category', 'sku', 'status', 'is_featured', 'sort_order', 'specifications'],
                ['Premium Honey', 'Natural honey 100%', 'Food Products', 'HONEY-001', 'active', 'yes', '10', ''],
                ['Olive Oil', 'Cold pressed olive oil', 'Food Products', 'OLIVE-001', 'active', 'no', '20', ''],
            ]]);

        $result = $this->service->parseExcelFile('fake-path.xlsx');

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['products']);
        $this->assertEquals(2, $result['total_rows']);

        $first = $result['products'][0];
        $this->assertEquals('Premium Honey', $first['name']);
        $this->assertEquals('Food Products', $first['category']);
        $this->assertEquals('HONEY-001', $first['sku']);
        $this->assertEquals('active', $first['status']);
        $this->assertTrue($first['is_featured']);
        $this->assertEquals(10, $first['sort_order']);
        $this->assertFalse($first['is_error']);
    }

    public function test_parse_fails_with_empty_file(): void
    {
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([[]]);

        $result = $this->service->parseExcelFile('empty.xlsx');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('empty', strtolower($result['error']));
    }

    public function test_parse_fails_with_missing_required_columns(): void
    {
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([[
                ['name', 'description'],
                ['Test Product', 'A description'],
            ]]);

        $result = $this->service->parseExcelFile('missing-cols.xlsx');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Missing required columns', $result['error']);
    }

    public function test_parse_handles_missing_product_name(): void
    {
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([[
                ['product_name', 'category'],
                ['', 'Food'],
            ]]);

        $result = $this->service->parseExcelFile('no-name.xlsx');

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['products']);
        $this->assertTrue($result['products'][0]['is_error']);
    }

    public function test_parse_skips_empty_rows(): void
    {
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([[
                ['product_name', 'category'],
                ['Product A', 'Category A'],
                [null, null],
                ['', ''],
                ['Product B', 'Category B'],
            ]]);

        $result = $this->service->parseExcelFile('with-gaps.xlsx');

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['products']);
    }

    public function test_parse_status_values(): void
    {
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([[
                ['product_name', 'category', 'status'],
                ['Active Product', 'Cat', 'active'],
                ['Inactive Product', 'Cat', 'inactive'],
                ['Disabled Product', 'Cat', 'disabled'],
                ['Default Product', 'Cat', ''],
            ]]);

        $result = $this->service->parseExcelFile('status-test.xlsx');

        $this->assertEquals('active', $result['products'][0]['status']);
        $this->assertEquals('inactive', $result['products'][1]['status']);
        $this->assertEquals('inactive', $result['products'][2]['status']);
        $this->assertEquals('active', $result['products'][3]['status']);
    }

    public function test_parse_boolean_values(): void
    {
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([[
                ['product_name', 'category', 'is_featured'],
                ['Product 1', 'Cat', 'yes'],
                ['Product 2', 'Cat', 'no'],
                ['Product 3', 'Cat', '1'],
                ['Product 4', 'Cat', '0'],
                ['Product 5', 'Cat', 'true'],
            ]]);

        $result = $this->service->parseExcelFile('bool-test.xlsx');

        $this->assertTrue($result['products'][0]['is_featured']);
        $this->assertFalse($result['products'][1]['is_featured']);
        $this->assertTrue($result['products'][2]['is_featured']);
        $this->assertFalse($result['products'][3]['is_featured']);
        $this->assertTrue($result['products'][4]['is_featured']);
    }

    public function test_parse_json_specifications(): void
    {
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([[
                ['product_name', 'category', 'specifications'],
                ['Product', 'Cat', '{"Weight":"500g","Origin":"UAE"}'],
            ]]);

        $result = $this->service->parseExcelFile('specs-test.xlsx');

        $specs = $result['products'][0]['specifications'];
        $this->assertNotNull($specs);
        $this->assertIsArray($specs);
        $this->assertEquals('Weight', $specs[0]['name']);
        $this->assertEquals('500g', $specs[0]['value']);
    }

    public function test_parse_key_value_specifications(): void
    {
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([[
                ['product_name', 'category', 'specifications'],
                ['Product', 'Cat', 'Weight:500g; Origin:UAE'],
            ]]);

        $result = $this->service->parseExcelFile('specs-kv.xlsx');

        $specs = $result['products'][0]['specifications'];
        $this->assertNotNull($specs);
        $this->assertCount(2, $specs);
        $this->assertEquals('Weight', $specs[0]['name']);
        $this->assertEquals('500g', $specs[0]['value']);
    }

    public function test_normalizes_header_names(): void
    {
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([[
                ['Product Name', 'Category', 'Is Featured'],
                ['Test', 'Cat', 'yes'],
            ]]);

        $result = $this->service->parseExcelFile('headers-test.xlsx');

        $this->assertTrue($result['success']);
        $this->assertEquals('Test', $result['products'][0]['name']);
        $this->assertTrue($result['products'][0]['is_featured']);
    }
}
