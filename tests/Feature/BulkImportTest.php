<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ImportLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

class BulkImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->regularUser = User::factory()->create(['role' => 'user']);
    }

    public function test_unauthenticated_user_cannot_import(): void
    {
        $response = $this->postJson('/api/admin/import/products', []);

        $response->assertStatus(401);
    }

    public function test_non_admin_cannot_import(): void
    {
        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->postJson('/api/admin/import/products', []);

        $response->assertStatus(403);
    }

    public function test_import_requires_excel_file(): void
    {
        $zipFile = UploadedFile::fake()->create('images.zip', 100, 'application/zip');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/import/products', [
                'zip_file' => $zipFile,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('excel_file');
    }

    public function test_import_requires_zip_file(): void
    {
        $excelFile = UploadedFile::fake()->create('products.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/import/products', [
                'excel_file' => $excelFile,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('zip_file');
    }

    public function test_import_validates_file_types(): void
    {
        $invalidFile = UploadedFile::fake()->create('document.txt', 100, 'text/plain');
        $zipFile = UploadedFile::fake()->create('images.zip', 100, 'application/zip');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/import/products', [
                'excel_file' => $invalidFile,
                'zip_file' => $zipFile,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('excel_file');
    }

    public function test_import_validates_file_size(): void
    {
        $largeExcel = UploadedFile::fake()->create('products.xlsx', 11000, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $zipFile = UploadedFile::fake()->create('images.zip', 100, 'application/zip');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/import/products', [
                'excel_file' => $largeExcel,
                'zip_file' => $zipFile,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('excel_file');
    }

    public function test_unauthenticated_user_cannot_view_logs(): void
    {
        $response = $this->getJson('/api/admin/import/logs');

        $response->assertStatus(401);
    }

    public function test_admin_can_view_import_logs(): void
    {
        ImportLog::create([
            'user_id' => $this->admin->id,
            'excel_file_name' => 'test.xlsx',
            'zip_file_name' => 'images.zip',
            'total_rows' => 10,
            'successful_imports' => 8,
            'failed_imports' => 2,
            'status' => 'completed',
            'started_at' => now(),
            'completed_at' => now(),
            'processing_time_seconds' => 30,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/import/logs');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'pagination' => ['page', 'limit', 'total', 'pages'],
        ]);
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(1, 'data');
    }

    public function test_admin_can_view_log_details(): void
    {
        $log = ImportLog::create([
            'user_id' => $this->admin->id,
            'excel_file_name' => 'test.xlsx',
            'zip_file_name' => 'images.zip',
            'total_rows' => 5,
            'successful_imports' => 3,
            'failed_imports' => 2,
            'status' => 'completed',
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/import/logs/{$log->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.id', $log->id);
    }

    public function test_user_cannot_view_other_users_logs(): void
    {
        $log = ImportLog::create([
            'user_id' => $this->admin->id,
            'excel_file_name' => 'test.xlsx',
            'zip_file_name' => 'images.zip',
            'status' => 'completed',
        ]);

        $otherAdmin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($otherAdmin, 'sanctum')
            ->getJson("/api/admin/import/logs/{$log->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_download_template(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->get('/api/admin/import/template');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_import_logs_only_show_own_records(): void
    {
        ImportLog::create([
            'user_id' => $this->admin->id,
            'excel_file_name' => 'admin.xlsx',
            'zip_file_name' => 'admin.zip',
            'status' => 'completed',
        ]);

        $otherAdmin = User::factory()->create(['role' => 'admin']);
        ImportLog::create([
            'user_id' => $otherAdmin->id,
            'excel_file_name' => 'other.xlsx',
            'zip_file_name' => 'other.zip',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/import/logs');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.excel_file_name', 'admin.xlsx');
    }
}
