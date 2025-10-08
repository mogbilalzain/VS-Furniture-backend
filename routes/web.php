<?php

use Illuminate\Support\Facades\Route;
use App\Models\Product;
use App\Models\ProductImage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Image Serving Routes
|--------------------------------------------------------------------------
|
| Routes to serve images from the uploads directory with proper headers
| and caching support for better performance
|
*/

// Route لخدمة الصور من مجلد uploads
Route::get('/uploads/{category}/{path}', function ($category, $path) {
    try {
        // فحص المجلد المسموح
        $allowedCategories = ['images', 'files'];
        if (!in_array($category, $allowedCategories)) {
            abort(404, 'Category not found');
        }

        // بناء المسار الكامل
        $fullPath = base_path("uploads/{$category}/{$path}");
        
        // التحقق من وجود الملف
        if (!file_exists($fullPath) || !is_file($fullPath)) {
            abort(404, 'File not found');
        }

        // الحصول على معلومات الملف
        $mimeType = mime_content_type($fullPath);
        $fileSize = filesize($fullPath);
        $lastModified = filemtime($fullPath);

        // إعداد headers للـ caching
        $response = response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Length' => $fileSize,
            'Cache-Control' => 'public, max-age=31536000', // سنة واحدة
            'Expires' => gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT',
            'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
            'ETag' => '"' . md5($fullPath . $lastModified) . '"',
        ]);

        // دعم If-Modified-Since header
        $ifModifiedSince = request()->header('If-Modified-Since');
        if ($ifModifiedSince && strtotime($ifModifiedSince) >= $lastModified) {
            return response('', 304);
        }

        return $response;

    } catch (\Exception $e) {
        \Log::error('Image serving error', [
            'category' => $category,
            'path' => $path,
            'error' => $e->getMessage()
        ]);
        
        abort(500, 'Internal server error');
    }
})->where('path', '.*')->name('uploads.serve');

Route::get('/test-product-images', function () {
    $product = Product::find(54);
    
    if (!$product) {
        return response()->json(['error' => 'Product not found']);
    }
    
    $activeImages = $product->activeImages;
    $allImages = $product->images;
    
    return response()->json([
        'product' => [
            'id' => $product->id,
            'name' => $product->name,
            'image' => $product->image,
            'image_url' => $product->image_url,
        ],
        'active_images_count' => $activeImages->count(),
        'all_images_count' => $allImages->count(),
        'active_images' => $activeImages->map(function($img) {
            return [
                'id' => $img->id,
                'image_url' => $img->image_url,
                'full_image_url' => $img->full_image_url,
                'is_primary' => $img->is_primary,
                'is_active' => $img->is_active,
                'sort_order' => $img->sort_order,
            ];
        }),
        'all_images' => $allImages->map(function($img) {
            return [
                'id' => $img->id,
                'image_url' => $img->image_url,
                'full_image_url' => $img->full_image_url,
                'is_primary' => $img->is_primary,
                'is_active' => $img->is_active,
                'sort_order' => $img->sort_order,
            ];
        }),
    ]);
});

/*
|--------------------------------------------------------------------------
| Include Testing Routes
|--------------------------------------------------------------------------
*/
if (app()->environment(['local', 'development', 'testing'])) {
    require __DIR__ . '/test-new-images.php';
}