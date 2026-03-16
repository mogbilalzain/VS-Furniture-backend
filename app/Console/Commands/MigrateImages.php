<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Category;
use App\Models\Solution;
use App\Models\SolutionImage;
use App\Helpers\ImageHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateImages extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'images:migrate {--dry-run : Run without making changes} {--category= : Migrate specific category only}';

    /**
     * The console command description.
     */
    protected $description = 'Migrate images from old storage system to new uploads directory';

    /**
     * Statistics for reporting
     */
    private $stats = [
        'solutions_covers' => ['migrated' => 0, 'failed' => 0, 'skipped' => 0],
        'solutions_gallery' => ['migrated' => 0, 'failed' => 0, 'skipped' => 0],
        'products' => ['migrated' => 0, 'failed' => 0, 'skipped' => 0],
        'categories' => ['migrated' => 0, 'failed' => 0, 'skipped' => 0],
        'certifications' => ['migrated' => 0, 'failed' => 0, 'skipped' => 0],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Image Migration Process...');
        
        $isDryRun = $this->option('dry-run');
        $specificCategory = $this->option('category');
        
        if ($isDryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
        }

        $this->info('📊 Current Storage Status:');
        $this->displayCurrentStatus();

        // إنشاء مجلدات النظام الجديد
        $this->createNewDirectories($isDryRun);

        // تنفيذ عمليات النقل
        if (!$specificCategory || $specificCategory === 'solutions') {
            $this->migrateSolutionsImages($isDryRun);
        }
        
        if (!$specificCategory || $specificCategory === 'products') {
            $this->migrateProductsImages($isDryRun);
        }
        
        if (!$specificCategory || $specificCategory === 'categories') {
            $this->migrateCategoriesImages($isDryRun);
        }
        
        if (!$specificCategory || $specificCategory === 'certifications') {
            $this->migrateCertificationsImages($isDryRun);
        }

        // عرض التقرير النهائي
        $this->displayFinalReport();

        if (!$isDryRun) {
            $this->info('✅ Migration completed successfully!');
            $this->warn('⚠️  Remember to:');
            $this->line('   - Test image loading on your website');
            $this->line('   - Update your .htaccess if needed');
            $this->line('   - Create backups of old directories before cleanup');
        } else {
            $this->info('🔍 Dry run completed. Run without --dry-run to apply changes.');
        }

        return Command::SUCCESS;
    }

    /**
     * Display current storage status
     */
    private function displayCurrentStatus()
    {
        $this->line('');
        $this->line('📂 Current Files:');
        
        // Solutions
        $solutionsCovers = glob(public_path('images/solutions/covers/*'));
        $solutionsGallery = glob(public_path('images/solutions/gallery/*'));
        $this->line("   Solutions Covers: " . count($solutionsCovers) . " files");
        $this->line("   Solutions Gallery: " . count($solutionsGallery) . " files");
        
        // Products (Storage)
        $productDirs = Storage::disk('public')->directories('images/products');
        $productFiles = 0;
        foreach ($productDirs as $dir) {
            $productFiles += count(Storage::disk('public')->files($dir));
        }
        $this->line("   Products: {$productFiles} files in " . count($productDirs) . " directories");
        
        // Categories (Storage)
        $categoryFiles = Storage::disk('public')->files('images/categories');
        $this->line("   Categories: " . count($categoryFiles) . " files");
        
        // Certifications
        $certifications = glob(public_path('images/certifications/*'));
        $this->line("   Certifications: " . count($certifications) . " files");
        
        $this->line('');
    }

    /**
     * Create new directory structure
     */
    private function createNewDirectories($isDryRun)
    {
        $this->info('📁 Creating new directory structure...');
        
        $directories = [
            'images/solutions/covers',
            'images/solutions/gallery',
            'images/products',
            'images/categories',
            'images/certifications',
            'files/products',
            'files/contacts'
        ];

        foreach ($directories as $dir) {
            if (!$isDryRun) {
                Storage::disk('uploads')->makeDirectory($dir, 0755, true);
            }
            $this->line("   ✓ {$dir}");
        }
    }

    /**
     * Migrate Solutions images
     */
    private function migrateSolutionsImages($isDryRun)
    {
        $this->info('🔄 Migrating Solutions images...');
        
        // Migrate covers
        $this->migrateDirectoryFiles(
            public_path('images/solutions/covers'),
            'images/solutions/covers',
            'solutions_covers',
            $isDryRun
        );
        
        // Migrate gallery
        $this->migrateDirectoryFiles(
            public_path('images/solutions/gallery'),
            'images/solutions/gallery',
            'solutions_gallery',
            $isDryRun
        );

        // Update database references for solutions
        if (!$isDryRun) {
            $this->updateSolutionsDatabaseReferences();
        }
    }

    /**
     * Migrate Products images
     */
    private function migrateProductsImages($isDryRun)
    {
        $this->info('🔄 Migrating Products images...');
        
        $productDirs = Storage::disk('public')->directories('images/products');
        
        foreach ($productDirs as $productDir) {
            $productId = basename($productDir);
            $files = Storage::disk('public')->files($productDir);
            
            foreach ($files as $file) {
                $this->migrateStorageFile($file, "images/products/{$productId}", 'products', $isDryRun);
            }
        }

        // Update database references for products
        if (!$isDryRun) {
            $this->updateProductsDatabaseReferences();
        }
    }

    /**
     * Migrate Categories images
     */
    private function migrateCategoriesImages($isDryRun)
    {
        $this->info('🔄 Migrating Categories images...');
        
        $files = Storage::disk('public')->files('images/categories');
        
        foreach ($files as $file) {
            $this->migrateStorageFile($file, 'images/categories', 'categories', $isDryRun);
        }

        // Update database references for categories
        if (!$isDryRun) {
            $this->updateCategoriesDatabaseReferences();
        }
    }

    /**
     * Migrate Certifications images
     */
    private function migrateCertificationsImages($isDryRun)
    {
        $this->info('🔄 Migrating Certifications images...');
        
        $this->migrateDirectoryFiles(
            public_path('images/certifications'),
            'images/certifications',
            'certifications',
            $isDryRun
        );
    }

    /**
     * Migrate files from a directory
     */
    private function migrateDirectoryFiles($sourceDir, $targetDir, $category, $isDryRun)
    {
        if (!is_dir($sourceDir)) {
            return;
        }

        $files = glob($sourceDir . '/*');
        
        foreach ($files as $file) {
            if (!is_file($file)) continue;
            
            $filename = basename($file);
            $targetPath = "{$targetDir}/{$filename}";
            
            try {
                if (!$isDryRun) {
                    // إنشاء المجلد إذا لم يكن موجوداً
                    Storage::disk('uploads')->makeDirectory($targetDir, 0755, true);
                    
                    // نسخ الملف
                    $fileContent = file_get_contents($file);
                    Storage::disk('uploads')->put($targetPath, $fileContent);
                }
                
                $this->stats[$category]['migrated']++;
                $this->line("   ✓ {$filename}");
                
            } catch (\Exception $e) {
                $this->stats[$category]['failed']++;
                $this->error("   ✗ Failed to migrate {$filename}: " . $e->getMessage());
                Log::error("Image migration failed", [
                    'file' => $file,
                    'target' => $targetPath,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Migrate files from Laravel storage
     */
    private function migrateStorageFile($file, $targetDir, $category, $isDryRun)
    {
        $filename = basename($file);
        $targetPath = "{$targetDir}/{$filename}";
        
        try {
            if (!$isDryRun) {
                // إنشاء المجلد إذا لم يكن موجوداً
                Storage::disk('uploads')->makeDirectory($targetDir, 0755, true);
                
                // نسخ الملف
                $fileContent = Storage::disk('public')->get($file);
                Storage::disk('uploads')->put($targetPath, $fileContent);
            }
            
            $this->stats[$category]['migrated']++;
            $this->line("   ✓ {$filename}");
            
        } catch (\Exception $e) {
            $this->stats[$category]['failed']++;
            $this->error("   ✗ Failed to migrate {$filename}: " . $e->getMessage());
            Log::error("Storage image migration failed", [
                'file' => $file,
                'target' => $targetPath,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update Solutions database references
     */
    private function updateSolutionsDatabaseReferences()
    {
        $this->info('🔄 Updating Solutions database references...');
        
        // Update solutions table
        $solutions = Solution::whereNotNull('cover_image')->get();
        foreach ($solutions as $solution) {
            $oldPath = $solution->cover_image;
            if (strpos($oldPath, '/images/solutions/') === 0) {
                $newPath = '/uploads' . $oldPath;
                $solution->update(['cover_image' => $newPath]);
                $this->line("   ✓ Updated solution {$solution->id} cover image");
            }
        }

        // Update solution_images table if exists
        if (DB::getSchemaBuilder()->hasTable('solution_images')) {
            $solutionImages = SolutionImage::whereNotNull('image_path')->get();
            foreach ($solutionImages as $image) {
                $oldPath = $image->image_path;
                if (strpos($oldPath, '/images/solutions/') === 0) {
                    $newPath = '/uploads' . $oldPath;
                    $image->update(['image_path' => $newPath]);
                    $this->line("   ✓ Updated solution image {$image->id}");
                }
            }
        }
    }

    /**
     * Update Products database references
     */
    private function updateProductsDatabaseReferences()
    {
        $this->info('🔄 Updating Products database references...');
        
        $productImages = ProductImage::where('image_url', 'like', '/storage/images/products/%')->get();
        
        foreach ($productImages as $image) {
            $oldUrl = $image->image_url;
            // Convert: /storage/images/products/54/filename.jpg -> /uploads/images/products/54/filename.jpg
            $newUrl = str_replace('/storage/', '/uploads/', $oldUrl);
            
            $image->update(['image_url' => $newUrl]);
            $this->line("   ✓ Updated product image {$image->id}");
        }
    }

    /**
     * Update Categories database references
     */
    private function updateCategoriesDatabaseReferences()
    {
        $this->info('🔄 Updating Categories database references...');
        
        $categories = Category::where('image', 'like', 'images/categories/%')->get();
        
        foreach ($categories as $category) {
            $oldPath = $category->image;
            // Convert: images/categories/filename.jpg -> /uploads/images/categories/filename.jpg
            $newPath = '/uploads/' . $oldPath;
            
            $category->update(['image' => $newPath]);
            $this->line("   ✓ Updated category {$category->id} image");
        }
    }

    /**
     * Display final migration report
     */
    private function displayFinalReport()
    {
        $this->info('📊 Migration Report:');
        $this->line('');
        
        $totalMigrated = 0;
        $totalFailed = 0;
        $totalSkipped = 0;
        
        foreach ($this->stats as $category => $stats) {
            $this->line("📂 " . ucfirst(str_replace('_', ' ', $category)) . ":");
            $this->line("   ✅ Migrated: {$stats['migrated']}");
            $this->line("   ❌ Failed: {$stats['failed']}");
            $this->line("   ⏭️  Skipped: {$stats['skipped']}");
            $this->line("");
            
            $totalMigrated += $stats['migrated'];
            $totalFailed += $stats['failed'];
            $totalSkipped += $stats['skipped'];
        }
        
        $this->info('🎯 Summary:');
        $this->line("   Total Migrated: {$totalMigrated}");
        $this->line("   Total Failed: {$totalFailed}");
        $this->line("   Total Skipped: {$totalSkipped}");
        
        if ($totalFailed > 0) {
            $this->warn("⚠️  {$totalFailed} files failed to migrate. Check logs for details.");
        }
    }
}






