<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use App\Helpers\ImageHelper;

/**
 * يوحّد كل المسارات والملفات على شكل واحد:
 *   - الملف الفعلي تحت: base_path('uploads/images/{category}/...')
 *   - القيمة في DB:    /uploads/images/{category}/...
 *
 * الأمر idempotent — آمن للتشغيل مرات متعددة.
 */
class ImagesUnifyPaths extends Command
{
    protected $signature = 'images:unify
                            {--dry-run : معاينة دون تغيير شيء}
                            {--no-files : تخطي نقل الملفات (يحدّث DB فقط)}
                            {--no-db : تخطي تحديث DB (ينقل الملفات فقط)}
                            {--quiet-when-clean : لا يطبع أي شيء إذا لا يوجد عمل}';

    protected $description = 'يوحّد جميع مسارات الصور إلى /uploads/images/{category}/... في DB والقرص';

    /** @var array<string,array{table:string,column:string,default_category:string}> */
    private array $tables = [
        'categories'      => ['table' => 'categories',      'column' => 'image',     'default_category' => 'categories'],
        'product_images'  => ['table' => 'product_images',  'column' => 'image_url', 'default_category' => 'products'],
        'materials'       => ['table' => 'materials',       'column' => 'image_url', 'default_category' => 'materials'],
        'certifications'  => ['table' => 'certifications',  'column' => 'image_url', 'default_category' => 'certifications'],
        'solutions'       => ['table' => 'solutions',       'column' => 'cover_image','default_category'=> 'solutions'],
        'solution_images' => ['table' => 'solution_images', 'column' => 'image_path','default_category' => 'solutions'],
    ];

    private bool $dryRun = false;

    private array $stats = [
        'files_moved' => 0,
        'files_skipped_exists' => 0,
        'files_missing' => 0,
        'rows_updated' => 0,
        'rows_unchanged' => 0,
    ];

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');
        $skipFiles   = (bool) $this->option('no-files');
        $skipDb      = (bool) $this->option('no-db');
        $quietClean  = (bool) $this->option('quiet-when-clean');

        $this->ensureBaseDirs();

        if (!$skipFiles) {
            $this->migrateFiles();
        }

        if (!$skipDb) {
            $this->normalizeDbPaths();
        }

        $hasWork = $this->stats['files_moved'] > 0
            || $this->stats['rows_updated'] > 0
            || $this->stats['files_missing'] > 0;

        if ($quietClean && !$hasWork && !$this->dryRun) {
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info($this->dryRun ? '== DRY RUN: لم يتم تغيير أي شيء ==' : '== نتيجة التشغيل ==');
        $this->table(
            ['المقياس', 'القيمة'],
            [
                ['ملفات منقولة',       $this->stats['files_moved']],
                ['ملفات موجودة بالفعل', $this->stats['files_skipped_exists']],
                ['ملفات مفقودة',       $this->stats['files_missing']],
                ['صفوف DB محدّثة',      $this->stats['rows_updated']],
                ['صفوف DB كانت موحَّدة', $this->stats['rows_unchanged']],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * إنشاء base_path('uploads/images/{cat}') إن لم يكن موجوداً.
     */
    private function ensureBaseDirs(): void
    {
        foreach (ImageHelper::SUPPORTED_CATEGORIES as $cat) {
            $dir = base_path('uploads/images/' . $cat);
            if (!is_dir($dir) && !$this->dryRun) {
                File::makeDirectory($dir, 0775, true, true);
            }
        }
    }

    /**
     * البحث عن ملفات صور في كل المواقع القديمة المحتملة ونقلها إلى uploads/images/{cat}/.
     */
    private function migrateFiles(): void
    {
        $sourceDirs = [
            // المسار الكلاسيكي لـ Laravel storage:link
            storage_path('app/public/images'),
            storage_path('app/public'),
            // المسار غير القياسي الذي ذكره المستخدم
            storage_path('public/images'),
            storage_path('public'),
            // إذا كان symlink قديم فيه ملفات حقيقية
            base_path('public/storage/images'),
            base_path('public/storage'),
            // مجلدات قديمة في public
            base_path('public/images'),
        ];

        foreach ($sourceDirs as $src) {
            if (!is_dir($src)) {
                continue;
            }
            $this->scanAndMove($src);
        }
    }

    /**
     * مسح مجلد بشكل تكراري ونقل الصور إلى uploads/images/{cat}/.
     */
    private function scanAndMove(string $sourceDir): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if (!$file->isFile()) {
                continue;
            }
            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ImageHelper::SUPPORTED_MIMES, true)) {
                continue;
            }

            $sourcePath = $file->getPathname();
            // نتجنّب نقل الملفات التي هي أصلاً تحت uploads/
            $uploadsRoot = base_path('uploads' . DIRECTORY_SEPARATOR);
            if (str_starts_with($sourcePath, $uploadsRoot)) {
                continue;
            }

            $relative = ltrim(str_replace([base_path() . DIRECTORY_SEPARATOR, '\\'], ['', '/'], $sourcePath), '/');
            // مثال: storage/app/public/images/products/sub/file.jpg أو storage/public/file.jpg
            $category = $this->detectCategoryFromPath($relative);

            $destDir = base_path('uploads/images/' . $category);
            $destPath = $destDir . DIRECTORY_SEPARATOR . $file->getFilename();

            if (file_exists($destPath)) {
                $this->stats['files_skipped_exists']++;
                $this->line("[skip-exists] {$relative}");
                continue;
            }

            if ($this->dryRun) {
                $this->line("[would-move] {$relative}  ->  uploads/images/{$category}/" . $file->getFilename());
                $this->stats['files_moved']++;
                continue;
            }

            if (!is_dir($destDir)) {
                File::makeDirectory($destDir, 0775, true, true);
            }

            if (@copy($sourcePath, $destPath)) {
                $this->stats['files_moved']++;
                $this->line("[moved] {$relative}  ->  uploads/images/{$category}/" . $file->getFilename());
            } else {
                $this->stats['files_missing']++;
                $this->error("[fail-copy] {$relative}");
            }
        }
    }

    /**
     * استنتاج فئة الصورة من المسار النسبي.
     */
    private function detectCategoryFromPath(string $relative): string
    {
        $relativeLower = strtolower($relative);
        foreach (ImageHelper::SUPPORTED_CATEGORIES as $cat) {
            if (str_contains($relativeLower, '/' . $cat . '/') || str_contains($relativeLower, $cat . '/')) {
                return $cat;
            }
        }
        return 'products'; // افتراضي
    }

    /**
     * توحيد قيم DB في كل الجداول المُسجّلة.
     */
    private function normalizeDbPaths(): void
    {
        $backup = [];

        foreach ($this->tables as $key => $info) {
            if (!Schema::hasTable($info['table']) || !Schema::hasColumn($info['table'], $info['column'])) {
                continue;
            }

            $col = $info['column'];
            $rows = DB::table($info['table'])
                ->select('id', $col)
                ->whereNotNull($col)
                ->where($col, '!=', '')
                ->get();

            foreach ($rows as $row) {
                $current = $row->{$col};
                if (preg_match('#^https?://#i', (string) $current)) {
                    $this->stats['rows_unchanged']++;
                    continue;
                }
                $normalized = ImageHelper::normalizeToUploadsPath($current, $info['default_category']);
                if ($normalized === null || $normalized === $current) {
                    $this->stats['rows_unchanged']++;
                    continue;
                }

                $backup[] = [
                    'table' => $info['table'],
                    'column' => $col,
                    'id' => $row->id,
                    'old' => $current,
                    'new' => $normalized,
                ];

                if ($this->dryRun) {
                    $this->line("[would-update] {$info['table']}#{$row->id}  {$current}  ->  {$normalized}");
                    $this->stats['rows_updated']++;
                    continue;
                }

                DB::table($info['table'])
                    ->where('id', $row->id)
                    ->update([$col => $normalized]);

                $this->stats['rows_updated']++;
                $this->line("[updated] {$info['table']}#{$row->id}  ->  {$normalized}");
            }
        }

        if (!$this->dryRun && !empty($backup)) {
            $this->saveBackup($backup);
        }
    }

    /**
     * حفظ نسخة احتياطية بقيم DB قبل التعديل.
     */
    private function saveBackup(array $rows): void
    {
        $dir = storage_path('app/backups/image-paths');
        if (!is_dir($dir)) {
            File::makeDirectory($dir, 0775, true, true);
        }
        $file = $dir . '/unify-' . date('Ymd-His') . '.json';
        file_put_contents($file, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $this->info("نسخة احتياطية: {$file}");
    }
}
