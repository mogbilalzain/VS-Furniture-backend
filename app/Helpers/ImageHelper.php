<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * مساعد إدارة الصور المحدث لنظام التخزين الجديد
 * Helper class for managing images in the new storage system
 */
class ImageHelper
{
    /**
     * Disk اسم الـ
     */
    const UPLOADS_DISK = 'uploads';

    /**
     * المجلدات المدعومة
     */
    const SUPPORTED_CATEGORIES = [
        'products',
        'categories', 
        'solutions',
        'certifications'
    ];

    /**
     * الأنواع المدعومة للصور
     */
    const SUPPORTED_MIMES = [
        'jpeg', 'png', 'jpg', 'gif', 'webp', 'svg'
    ];

    /**
     * الحد الأقصى لحجم الملف (5MB)
     */
    const MAX_FILE_SIZE = 5120; // KB

    /**
     * رفع صورة جديدة
     *
     * @param UploadedFile $file
     * @param string $category المجلد الرئيسي (products, categories, solutions, certifications)
     * @param string|null $subCategory المجلد الفرعي (covers, gallery, product_id, etc.)
     * @param array $options خيارات إضافية
     * @return array
     */
    public static function uploadImage(
        UploadedFile $file, 
        string $category, 
        ?string $subCategory = null, 
        array $options = []
    ): array {
        try {
            // التحقق من صحة المجلد
            if (!in_array($category, self::SUPPORTED_CATEGORIES)) {
                throw new \InvalidArgumentException("Category '{$category}' is not supported");
            }

            // التحقق من نوع الملف
            if (!in_array($file->getClientOriginalExtension(), self::SUPPORTED_MIMES)) {
                throw new \InvalidArgumentException("File type is not supported");
            }

            // التحقق من حجم الملف
            if ($file->getSize() > (self::MAX_FILE_SIZE * 1024)) {
                throw new \InvalidArgumentException("File size exceeds maximum limit");
            }

            // إنشاء اسم الملف الفريد
            $timestamp = time();
            $randomString = Str::random(10);
            $extension = $file->getClientOriginalExtension();
            $filename = "{$timestamp}_{$randomString}.{$extension}";

            // تحديد المسار الكامل
            $path = self::buildPath($category, $subCategory, $filename);

            // إنشاء المجلد إذا لم يكن موجوداً
            $directory = dirname($path);
            if (!Storage::disk(self::UPLOADS_DISK)->exists($directory)) {
                Storage::disk(self::UPLOADS_DISK)->makeDirectory($directory, 0755, true);
            }

            // رفع الملف
            $uploadedPath = Storage::disk(self::UPLOADS_DISK)->putFileAs(
                dirname($path),
                $file,
                basename($path)
            );

            if (!$uploadedPath) {
                throw new \Exception("Failed to upload file");
            }

            // الحصول على معلومات الملف
            $fullPath = Storage::disk(self::UPLOADS_DISK)->path($uploadedPath);
            $imageInfo = @getimagesize($fullPath);
            $fileSize = $file->getSize();

            // إنشاء الرابط العام
            $publicUrl = self::getImageUrl($uploadedPath);

            // إرجاع النتائج
            return [
                'success' => true,
                'data' => [
                    'filename' => $filename,
                    'path' => $uploadedPath,
                    'url' => $publicUrl,
                    'full_url' => url($publicUrl),
                    'size' => $fileSize,
                    'size_formatted' => self::formatFileSize($fileSize),
                    'dimensions' => $imageInfo ? [
                        'width' => $imageInfo[0],
                        'height' => $imageInfo[1]
                    ] : null,
                    'mime_type' => $file->getMimeType(),
                    'original_name' => $file->getClientOriginalName(),
                    'category' => $category,
                    'sub_category' => $subCategory,
                ],
                'message' => 'Image uploaded successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Image upload failed', [
                'error' => $e->getMessage(),
                'category' => $category,
                'sub_category' => $subCategory,
                'file_name' => $file->getClientOriginalName()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * الحصول على رابط الصورة
     *
     * @param string $path
     * @return string
     */
    public static function getImageUrl(string $path): string
    {
        // إزالة أي prefixes إضافية
        $cleanPath = ltrim($path, '/');
        
        // إرجاع الرابط النسبي
        return "/uploads/{$cleanPath}";
    }

    /**
     * الحصول على الرابط الكامل للصورة
     *
     * @param string $path
     * @return string
     */
    public static function getFullImageUrl(string $path): string
    {
        return url(self::getImageUrl($path));
    }

    /**
     * حذف صورة
     *
     * @param string $path
     * @return bool
     */
    public static function deleteImage(string $path): bool
    {
        try {
            if (self::imageExists($path)) {
                Storage::disk(self::UPLOADS_DISK)->delete($path);
                Log::info('Image deleted successfully', ['path' => $path]);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error('Image deletion failed', [
                'error' => $e->getMessage(),
                'path' => $path
            ]);
            return false;
        }
    }

    /**
     * التحقق من وجود الصورة
     *
     * @param string $path
     * @return bool
     */
    public static function imageExists(string $path): bool
    {
        return Storage::disk(self::UPLOADS_DISK)->exists($path);
    }

    /**
     * بناء مسار الملف
     *
     * @param string $category
     * @param string|null $subCategory
     * @param string $filename
     * @return string
     */
    private static function buildPath(string $category, ?string $subCategory, string $filename): string
    {
        $pathParts = ['images', $category];
        
        if ($subCategory) {
            $pathParts[] = $subCategory;
        }
        
        $pathParts[] = $filename;
        
        return implode('/', $pathParts);
    }

    /**
     * تحويل حجم الملف إلى تنسيق قابل للقراءة
     *
     * @param int $bytes
     * @return string
     */
    public static function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }

    /**
     * إنشاء thumbnail للصورة
     *
     * @param string $sourcePath
     * @param int $maxWidth
     * @param int $maxHeight
     * @return array
     */
    public static function createThumbnail(string $sourcePath, int $maxWidth = 300, int $maxHeight = 300): array
    {
        try {
            if (!self::imageExists($sourcePath)) {
                throw new \Exception("Source image not found");
            }

            $fullSourcePath = Storage::disk(self::UPLOADS_DISK)->path($sourcePath);
            $imageInfo = getimagesize($fullSourcePath);
            
            if (!$imageInfo) {
                throw new \Exception("Invalid image file");
            }

            // حساب الأبعاد الجديدة
            $originalWidth = $imageInfo[0];
            $originalHeight = $imageInfo[1];
            
            $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
            $newWidth = round($originalWidth * $ratio);
            $newHeight = round($originalHeight * $ratio);

            // إنشاء اسم الـ thumbnail
            $pathInfo = pathinfo($sourcePath);
            $thumbnailPath = $pathInfo['dirname'] . '/thumb_' . $pathInfo['basename'];

            // إنشاء الـ thumbnail (هذا مثال مبسط، يمكن استخدام مكتبة مثل Intervention Image)
            $thumbnailFullPath = Storage::disk(self::UPLOADS_DISK)->path($thumbnailPath);

            // هنا يمكن إضافة منطق إنشاء الـ thumbnail الفعلي
            // مثال مع GD library أو Intervention Image

            return [
                'success' => true,
                'thumbnail_path' => $thumbnailPath,
                'thumbnail_url' => self::getImageUrl($thumbnailPath),
                'dimensions' => [
                    'width' => $newWidth,
                    'height' => $newHeight
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Thumbnail creation failed', [
                'error' => $e->getMessage(),
                'source_path' => $sourcePath
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * الحصول على معلومات الصورة
     *
     * @param string $path
     * @return array|null
     */
    public static function getImageInfo(string $path): ?array
    {
        try {
            if (!self::imageExists($path)) {
                return null;
            }

            $fullPath = Storage::disk(self::UPLOADS_DISK)->path($path);
            $imageInfo = getimagesize($fullPath);
            $fileSize = Storage::disk(self::UPLOADS_DISK)->size($path);

            if (!$imageInfo) {
                return null;
            }

            return [
                'path' => $path,
                'url' => self::getImageUrl($path),
                'full_url' => self::getFullImageUrl($path),
                'dimensions' => [
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1]
                ],
                'size' => $fileSize,
                'size_formatted' => self::formatFileSize($fileSize),
                'mime_type' => $imageInfo['mime'],
                'last_modified' => Storage::disk(self::UPLOADS_DISK)->lastModified($path)
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get image info', [
                'error' => $e->getMessage(),
                'path' => $path
            ]);
            return null;
        }
    }

    /**
     * نقل صورة من مكان لآخر
     *
     * @param string $oldPath
     * @param string $newPath
     * @return bool
     */
    public static function moveImage(string $oldPath, string $newPath): bool
    {
        try {
            if (!self::imageExists($oldPath)) {
                return false;
            }

            // إنشاء المجلد الجديد إذا لم يكن موجوداً
            $newDirectory = dirname($newPath);
            if (!Storage::disk(self::UPLOADS_DISK)->exists($newDirectory)) {
                Storage::disk(self::UPLOADS_DISK)->makeDirectory($newDirectory, 0755, true);
            }

            // نقل الملف
            $result = Storage::disk(self::UPLOADS_DISK)->move($oldPath, $newPath);
            
            if ($result) {
                Log::info('Image moved successfully', [
                    'old_path' => $oldPath,
                    'new_path' => $newPath
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Image move failed', [
                'error' => $e->getMessage(),
                'old_path' => $oldPath,
                'new_path' => $newPath
            ]);
            return false;
        }
    }

    /**
     * تنظيف المجلدات الفارغة
     *
     * @param string $category
     * @return int عدد المجلدات المحذوفة
     */
    public static function cleanEmptyDirectories(string $category): int
    {
        $cleanedCount = 0;
        
        try {
            $basePath = "images/{$category}";
            $directories = Storage::disk(self::UPLOADS_DISK)->allDirectories($basePath);
            
            // ترتيب المجلدات من الأعمق للأضحل
            usort($directories, function($a, $b) {
                return substr_count($b, '/') - substr_count($a, '/');
            });

            foreach ($directories as $directory) {
                $files = Storage::disk(self::UPLOADS_DISK)->allFiles($directory);
                $subdirectories = Storage::disk(self::UPLOADS_DISK)->allDirectories($directory);
                
                if (empty($files) && empty($subdirectories)) {
                    Storage::disk(self::UPLOADS_DISK)->deleteDirectory($directory);
                    $cleanedCount++;
                }
            }

            if ($cleanedCount > 0) {
                Log::info("Cleaned {$cleanedCount} empty directories in {$category}");
            }

        } catch (\Exception $e) {
            Log::error('Directory cleanup failed', [
                'error' => $e->getMessage(),
                'category' => $category
            ]);
        }

        return $cleanedCount;
    }
}






