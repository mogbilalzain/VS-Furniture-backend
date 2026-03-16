<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ZipExtractorService;

class ZipExtractorServiceTest extends TestCase
{
    protected ZipExtractorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ZipExtractorService();
    }

    public function test_match_images_to_products_finds_exact_matches(): void
    {
        $products = [
            ['name' => 'Premium Honey', 'is_error' => false],
            ['name' => 'Olive Oil', 'is_error' => false],
        ];

        $images = [
            ['name_without_ext' => 'Premium Honey', 'original_name' => 'Premium Honey.jpg', 'extension' => 'jpg', 'content' => 'fake', 'size' => 100],
            ['name_without_ext' => 'Olive Oil', 'original_name' => 'Olive Oil.png', 'extension' => 'png', 'content' => 'fake', 'size' => 200],
        ];

        $result = $this->service->matchImagesToProducts($products, $images);

        $this->assertArrayHasKey('Premium Honey', $result);
        $this->assertArrayHasKey('Olive Oil', $result);
        $this->assertCount(1, $result['Premium Honey']);
        $this->assertCount(1, $result['Olive Oil']);
    }

    public function test_match_images_detects_primary_image(): void
    {
        $products = [
            ['name' => 'Honey', 'is_error' => false],
        ];

        $images = [
            ['name_without_ext' => 'Honey', 'original_name' => 'Honey.jpg', 'extension' => 'jpg', 'content' => 'fake', 'size' => 100],
            ['name_without_ext' => 'Honey-1', 'original_name' => 'Honey-1.jpg', 'extension' => 'jpg', 'content' => 'fake', 'size' => 100],
            ['name_without_ext' => 'Honey-2', 'original_name' => 'Honey-2.jpg', 'extension' => 'jpg', 'content' => 'fake', 'size' => 100],
        ];

        $result = $this->service->matchImagesToProducts($products, $images);

        $this->assertCount(3, $result['Honey']);
        $this->assertTrue($result['Honey'][0]['is_primary']);
        $this->assertFalse($result['Honey'][1]['is_primary']);
        $this->assertFalse($result['Honey'][2]['is_primary']);
    }

    public function test_match_images_sorts_by_order(): void
    {
        $products = [
            ['name' => 'Product', 'is_error' => false],
        ];

        $images = [
            ['name_without_ext' => 'Product-3', 'original_name' => 'Product-3.jpg', 'extension' => 'jpg', 'content' => 'fake', 'size' => 100],
            ['name_without_ext' => 'Product-1', 'original_name' => 'Product-1.jpg', 'extension' => 'jpg', 'content' => 'fake', 'size' => 100],
            ['name_without_ext' => 'Product', 'original_name' => 'Product.jpg', 'extension' => 'jpg', 'content' => 'fake', 'size' => 100],
        ];

        $result = $this->service->matchImagesToProducts($products, $images);

        $this->assertEquals(0, $result['Product'][0]['sort_order']);
        $this->assertEquals(1, $result['Product'][1]['sort_order']);
        $this->assertEquals(3, $result['Product'][2]['sort_order']);
    }

    public function test_match_images_skips_error_products(): void
    {
        $products = [
            ['name' => 'Good Product', 'is_error' => false],
            ['name' => 'Bad Product', 'is_error' => true, 'error' => 'Invalid data'],
        ];

        $images = [
            ['name_without_ext' => 'Good Product', 'original_name' => 'Good Product.jpg', 'extension' => 'jpg', 'content' => 'fake', 'size' => 100],
            ['name_without_ext' => 'Bad Product', 'original_name' => 'Bad Product.jpg', 'extension' => 'jpg', 'content' => 'fake', 'size' => 100],
        ];

        $result = $this->service->matchImagesToProducts($products, $images);

        $this->assertArrayHasKey('Good Product', $result);
        $this->assertArrayNotHasKey('Bad Product', $result);
    }

    public function test_match_images_returns_empty_for_unmatched_products(): void
    {
        $products = [
            ['name' => 'No Image Product', 'is_error' => false],
        ];

        $images = [
            ['name_without_ext' => 'Other Image', 'original_name' => 'Other Image.jpg', 'extension' => 'jpg', 'content' => 'fake', 'size' => 100],
        ];

        $result = $this->service->matchImagesToProducts($products, $images);

        $this->assertArrayNotHasKey('No Image Product', $result);
    }

    public function test_extract_images_fails_with_invalid_path(): void
    {
        $result = $this->service->extractImages('/nonexistent/path/file.zip');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_extract_images_from_valid_zip(): void
    {
        $zipPath = $this->createTestZip(['test-image.jpg', 'another.png']);

        $result = $this->service->extractImages($zipPath);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['images']);
        $this->assertEquals(2, $result['total_images']);

        unlink($zipPath);
    }

    public function test_extract_images_skips_non_image_files(): void
    {
        $zipPath = $this->createTestZip(['image.jpg', 'document.pdf', 'script.js']);

        $result = $this->service->extractImages($zipPath);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['images']);
        $this->assertEquals('image.jpg', $result['images'][0]['original_name']);

        unlink($zipPath);
    }

    public function test_extract_images_skips_macosx_files(): void
    {
        $zipPath = $this->createTestZip(['image.jpg', '__MACOSX/image.jpg']);

        $result = $this->service->extractImages($zipPath);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['images']);

        unlink($zipPath);
    }

    private function createTestZip(array $filenames): string
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'test_zip_') . '.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);

        foreach ($filenames as $name) {
            $zip->addFromString($name, 'fake-content-' . $name);
        }

        $zip->close();
        return $zipPath;
    }
}
