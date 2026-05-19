<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Helpers\ImageHelper;

/**
 * تشخيص شامل لحالة الصور:
 *   - APP_URL الفعلي
 *   - عدد الملفات في كل مجلد محتمل
 *   - عينات من DB مع الرابط النهائي + هل الملف موجود فعلياً
 *
 * يساعد على معرفة سبب 404 بسرعة بدون الحاجة لاستكشاف يدوي طويل.
 */
class ImagesDiagnose extends Command
{
    protected $signature = 'images:diagnose
                            {--samples=3 : عدد العينات لكل جدول}
                            {--no-db : تخطي فحص الـ DB}';

    protected $description = 'فحص شامل لحالة الصور (config + filesystem + DB + رابط نهائي)';

    /** @var array<string,array{table:string,column:string,default_category:string}> */
    private array $tables = [
        'categories'      => ['table' => 'categories',      'column' => 'image',      'default_category' => 'categories'],
        'product_images'  => ['table' => 'product_images',  'column' => 'image_url',  'default_category' => 'products'],
        'materials'       => ['table' => 'materials',       'column' => 'image_url',  'default_category' => 'products'],
        'certifications'  => ['table' => 'certifications',  'column' => 'image_url',  'default_category' => 'certifications'],
        'solutions'       => ['table' => 'solutions',       'column' => 'cover_image','default_category' => 'solutions'],
        'solution_images' => ['table' => 'solution_images', 'column' => 'image_path', 'default_category' => 'solutions'],
    ];

    public function handle(): int
    {
        $this->info('=== Images Diagnose ===');
        $this->printConfig();
        $this->printFilesystem();

        if (!$this->option('no-db')) {
            $this->printDbSamples((int) $this->option('samples'));
        }

        $this->newLine();
        $this->info('=== انتهى التشخيص ===');
        return self::SUCCESS;
    }

    private function printConfig(): void
    {
        $this->newLine();
        $this->line('--- Config ---');
        $this->table(
            ['key', 'value'],
            [
                ['APP_URL (config)', (string) config('app.url')],
                ['APP_URL (env)',    (string) env('APP_URL', '(not set)')],
                ['APP_ENV',          (string) config('app.env')],
                ['filesystems.uploads.root', (string) config('filesystems.disks.uploads.root', '(not set)')],
                ['filesystems.public.root',  (string) config('filesystems.disks.public.root', '(not set)')],
            ]
        );
    }

    private function printFilesystem(): void
    {
        $this->newLine();
        $this->line('--- Filesystem (counts) ---');

        $dirs = [
            'uploads/images'             => base_path('uploads/images'),
            'uploads (root)'             => base_path('uploads'),
            'storage/app/public/images'  => storage_path('app/public/images'),
            'storage/app/public (root)'  => storage_path('app/public'),
            'storage/public/images'      => storage_path('public/images'),
            'storage/public (root)'      => storage_path('public'),
            'public/storage'             => base_path('public/storage'),
            'public/images'              => base_path('public/images'),
        ];

        $rows = [];
        foreach ($dirs as $label => $path) {
            $exists   = is_dir($path);
            $writable = $exists ? (is_writable($path) ? 'YES' : 'NO (خطأ صلاحيات!)') : '-';
            $count    = $exists ? $this->countImageFiles($path) : 0;
            $sample   = $exists ? $this->firstImageRelative($path) : '';
            $rows[]   = [
                $label,
                $exists ? 'YES' : 'NO',
                $writable,
                $exists ? (string) $count : '-',
                $sample,
            ];
        }

        $this->table(['directory', 'exists', 'writable', '#images', 'sample file'], $rows);
    }

    private function printDbSamples(int $samples): void
    {
        $this->newLine();
        $this->line('--- DB samples (with resolved URL + file existence) ---');

        foreach ($this->tables as $key => $info) {
            if (!Schema::hasTable($info['table']) || !Schema::hasColumn($info['table'], $info['column'])) {
                $this->warn("[skip] جدول/عمود غير موجود: {$info['table']}.{$info['column']}");
                continue;
            }

            $rows = DB::table($info['table'])
                ->select('id', $info['column'])
                ->whereNotNull($info['column'])
                ->where($info['column'], '!=', '')
                ->limit($samples)
                ->get();

            if ($rows->isEmpty()) {
                $this->line("[empty] {$info['table']}.{$info['column']}: لا يوجد قيم");
                continue;
            }

            $this->newLine();
            $this->line("table: <fg=cyan>{$info['table']}.{$info['column']}</> (default category: {$info['default_category']})");

            $tableRows = [];
            foreach ($rows as $row) {
                $raw   = (string) $row->{$info['column']};
                $norm  = ImageHelper::normalizeToUploadsPath($raw, $info['default_category']);
                $url   = ImageHelper::buildFullUrl($raw, $info['default_category']);
                $exist = $this->probeFile($norm);
                $tableRows[] = [
                    $row->id,
                    $this->shorten($raw, 35),
                    $this->shorten((string) $norm, 35),
                    $this->shorten((string) $url, 50),
                    $exist,
                ];
            }
            $this->table(['id', 'raw', 'normalized', 'final url', 'file exists'], $tableRows);
        }
    }

    /**
     * يفحص هل الملف موجود في أيٍّ من المجلدين المخدومين عبر nginx.
     *
     * /uploads/images/{cat}/x.jpg  -> uploads/images/{cat}/x.jpg                (الجديد)
     *                              -> storage/app/public/images/{cat}/x.jpg     (القديم — مظلة nginx)
     */
    private function probeFile(?string $normalized): string
    {
        if ($normalized === null || $normalized === '') {
            return 'n/a';
        }
        if (preg_match('#^https?://#i', $normalized)) {
            return 'remote';
        }
        $rel = ltrim($normalized, '/');

        $candidates = [];
        if (str_starts_with($rel, 'uploads/')) {
            $candidates[] = base_path($rel);
            $rest = substr($rel, strlen('uploads/'));
            $candidates[] = storage_path('app/public/' . $rest);
        } else {
            $candidates[] = base_path($rel);
            $candidates[] = storage_path('app/public/' . $rel);
        }

        foreach ($candidates as $c) {
            if (is_file($c)) {
                $loc = str_contains($c, DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR)
                    ? 'uploads'
                    : 'storage';
                return "OK ({$loc})";
            }
        }
        return 'MISSING';
    }

    private function countImageFiles(string $dir): int
    {
        $count = 0;
        try {
            $iter = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($iter as $f) {
                if (!$f->isFile()) continue;
                $ext = strtolower($f->getExtension());
                if (in_array($ext, ImageHelper::SUPPORTED_MIMES, true)) {
                    $count++;
                }
            }
        } catch (\Throwable $e) {
            return 0;
        }
        return $count;
    }

    private function firstImageRelative(string $dir): string
    {
        try {
            $iter = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($iter as $f) {
                if (!$f->isFile()) continue;
                $ext = strtolower($f->getExtension());
                if (in_array($ext, ImageHelper::SUPPORTED_MIMES, true)) {
                    $rel = str_replace([base_path() . DIRECTORY_SEPARATOR, '\\'], ['', '/'], $f->getPathname());
                    return $this->shorten($rel, 60);
                }
            }
        } catch (\Throwable $e) {
            return '';
        }
        return '';
    }

    private function shorten(string $s, int $max): string
    {
        if (mb_strlen($s) <= $max) return $s;
        $head = mb_substr($s, 0, (int) floor($max / 2) - 2);
        $tail = mb_substr($s, -((int) floor($max / 2) - 2));
        return $head . '...' . $tail;
    }
}
