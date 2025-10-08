<?php

use Illuminate\Support\Facades\Route;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Image System Testing Routes
|--------------------------------------------------------------------------
|
| Routes for testing the new image storage system
| Access via: /test-images/*
|
*/

Route::prefix('test-images')->group(function () {
    
    // Test image upload endpoint
    Route::post('/upload', function (Request $request) {
        try {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp,svg|max:5120',
                'category' => 'required|string|in:products,categories,solutions,certifications',
                'sub_category' => 'nullable|string'
            ]);

            $result = ImageHelper::uploadImage(
                $request->file('image'),
                $request->get('category'),
                $request->get('sub_category')
            );

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    });

    // Test image info endpoint
    Route::get('/info/{category}/{path}', function ($category, $path) {
        $fullPath = "images/{$category}/{$path}";
        $info = ImageHelper::getImageInfo($fullPath);
        
        return response()->json([
            'success' => $info !== null,
            'data' => $info
        ]);
    })->where('path', '.*');

    // Test image existence
    Route::get('/exists/{category}/{path}', function ($category, $path) {
        $fullPath = "images/{$category}/{$path}";
        $exists = ImageHelper::imageExists($fullPath);
        
        return response()->json([
            'exists' => $exists,
            'path' => $fullPath
        ]);
    })->where('path', '.*');

    // Test directory cleanup
    Route::post('/cleanup/{category}', function ($category) {
        $cleanedCount = ImageHelper::cleanEmptyDirectories($category);
        
        return response()->json([
            'success' => true,
            'cleaned_directories' => $cleanedCount
        ]);
    });

    // Test image deletion
    Route::delete('/delete/{category}/{path}', function ($category, $path) {
        $fullPath = "images/{$category}/{$path}";
        $deleted = ImageHelper::deleteImage($fullPath);
        
        return response()->json([
            'success' => $deleted,
            'path' => $fullPath
        ]);
    })->where('path', '.*');

    // Dashboard for testing
    Route::get('/dashboard', function () {
        return response()->json([
            'message' => 'Image System Testing Dashboard',
            'endpoints' => [
                'POST /test-images/upload' => 'Upload new image',
                'GET /test-images/info/{category}/{path}' => 'Get image info',
                'GET /test-images/exists/{category}/{path}' => 'Check if image exists',
                'POST /test-images/cleanup/{category}' => 'Clean empty directories',
                'DELETE /test-images/delete/{category}/{path}' => 'Delete image'
            ],
            'categories' => ImageHelper::SUPPORTED_CATEGORIES,
            'supported_mimes' => ImageHelper::SUPPORTED_MIMES,
            'max_file_size' => ImageHelper::MAX_FILE_SIZE . ' KB'
        ]);
    });

    // Get system statistics
    Route::get('/stats', function () {
        $stats = [];
        
        foreach (ImageHelper::SUPPORTED_CATEGORIES as $category) {
            $categoryPath = "images/{$category}";
            
            try {
                $allFiles = \Storage::disk('uploads')->allFiles($categoryPath);
                $allDirs = \Storage::disk('uploads')->allDirectories($categoryPath);
                
                $stats[$category] = [
                    'files_count' => count($allFiles),
                    'directories_count' => count($allDirs),
                    'total_size' => 0 // يمكن حسابه لاحقاً
                ];
                
                // حساب الحجم الإجمالي
                $totalSize = 0;
                foreach ($allFiles as $file) {
                    try {
                        $totalSize += \Storage::disk('uploads')->size($file);
                    } catch (\Exception $e) {
                        // تجاهل الملفات التي لا يمكن قراءتها
                    }
                }
                $stats[$category]['total_size'] = $totalSize;
                $stats[$category]['total_size_formatted'] = ImageHelper::formatFileSize($totalSize);
                
            } catch (\Exception $e) {
                $stats[$category] = [
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return response()->json([
            'success' => true,
            'statistics' => $stats,
            'disk_info' => [
                'uploads_disk_exists' => \Storage::disk('uploads') !== null,
                'base_path' => base_path('uploads')
            ]
        ]);
    });
});

