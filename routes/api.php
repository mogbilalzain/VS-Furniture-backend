<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\FilterController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\ProductFileController;
use App\Http\Controllers\Api\CertificationController;
use App\Http\Controllers\Api\ProductImageController;
use App\Http\Controllers\Api\MaterialCategoryController;
use App\Http\Controllers\Api\MaterialGroupController;
use App\Http\Controllers\Api\MaterialController;
use App\Http\Controllers\Api\ProductMaterialController;
use App\Http\Controllers\Api\SolutionController;
use App\Http\Controllers\Api\HomepageContentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ImportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// Public routes
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('/products/{product}/files/{file}/download', [ProductFileController::class, 'download'])->name('products.files.download');
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);
Route::get('/categories/{category}/products', [CategoryController::class, 'products']);
Route::get('/categories/{category}/properties', [PropertyController::class, 'getCategoryProperties']);
Route::get('/categories/{category}/properties/faceted', [PropertyController::class, 'getFacetedProperties']);
Route::get('/categories/{category}/property-groups', [PropertyController::class, 'getPropertyGroups']);
Route::get('/products/{product}/properties', [ProductController::class, 'getProductProperties']);
Route::get('/products/{product}/files', [ProductFileController::class, 'index']);
Route::get('/properties/{property}/values', [PropertyController::class, 'getPropertyValues']);
Route::get('/filters', [FilterController::class, 'index']);
Route::post('/filters/products', [FilterController::class, 'filterProducts']);
Route::post('/filters/stats', [FilterController::class, 'getFilterStats']);
Route::post('/contact', [ContactController::class, 'store']);
Route::get('/contact/{message}/files/{file}/download', [ContactController::class, 'downloadFile']);
Route::post('/orders', [OrderController::class, 'store']);

// Temporary public contact routes for debugging
Route::get('/public-contact', [ContactController::class, 'index']);
Route::get('/public-contact/unread-count', [ContactController::class, 'unreadCount']);
Route::get('/public-contact/recent', [ContactController::class, 'recent']);
Route::get('/public-contact/stats/overview', [ContactController::class, 'stats']);

// Certifications routes
Route::get('/certifications', [CertificationController::class, 'index']);
Route::get('/products/{product}/certifications', [CertificationController::class, 'getProductCertifications']);

// Product Images routes (public)
Route::get('/products/{product}/images', [ProductImageController::class, 'index']);

// Solutions routes (public)
Route::get('/solutions', [SolutionController::class, 'index']);
Route::get('/solutions/{solution}', [SolutionController::class, 'show']);

// Materials routes (public)
Route::get('/materials/categories', [MaterialCategoryController::class, 'index']);
Route::get('/materials/categories/{category}', [MaterialCategoryController::class, 'show']);
Route::get('/materials/groups', [MaterialGroupController::class, 'index']);
Route::get('/materials/groups/{group}', [MaterialGroupController::class, 'show']);
Route::get('/materials', [MaterialController::class, 'index']);
Route::get('/materials/{material}', [MaterialController::class, 'show']);
Route::get('/products/{product}/materials', [ProductMaterialController::class, 'index']);

// Homepage Content routes (public)
Route::get('/homepage-content', [HomepageContentController::class, 'index']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    
    // Admin only routes
    Route::middleware('admin')->group(function () {
        // Products (admin)
        Route::post('/admin/products', [ProductController::class, 'store']);
        Route::put('/admin/products/{product}', [ProductController::class, 'update']);
        Route::delete('/admin/products/{product}', [ProductController::class, 'destroy']);
        Route::get('/admin/products', [ProductController::class, 'adminIndex']);
        Route::post('/admin/products/upload-image', [ProductController::class, 'uploadImage']);
        
        // Product Properties (admin)
        Route::post('/products/{product}/properties', [ProductController::class, 'attachProperties']);
        Route::post('/admin/products/{product}/properties', [ProductController::class, 'attachProperties']);
        Route::get('/categories/{category}/properties-for-product', [ProductController::class, 'getCategoryPropertiesForProduct']);
        
        // Product Files (admin)
        Route::get('/admin/product-files', [ProductFileController::class, 'adminIndex']);
        Route::post('/admin/product-files', [ProductFileController::class, 'adminStore']);
        Route::put('/admin/product-files/{file}', [ProductFileController::class, 'adminUpdate']);
        Route::delete('/admin/product-files/{file}', [ProductFileController::class, 'adminDestroy']);
        Route::get('/admin/product-files/{file}/download', [ProductFileController::class, 'adminDownload']);
        Route::get('/admin/products/{product}/files', [ProductFileController::class, 'getByProduct']);
        
        // Legacy Product Files routes (admin only - keep for compatibility)
        Route::post('/products/{product}/files', [ProductFileController::class, 'store']);
        Route::put('/products/{product}/files/{file}', [ProductFileController::class, 'update']);
        Route::delete('/products/{product}/files/{file}', [ProductFileController::class, 'destroy']);
        
        // Properties Management (admin)
        Route::get('/admin/properties', [PropertyController::class, 'adminIndex']);
        Route::get('/admin/property-values', [PropertyController::class, 'adminPropertyValues']);
        Route::post('/admin/categories/{category}/properties', [PropertyController::class, 'storeProperty']);
        Route::put('/admin/properties/{property}', [PropertyController::class, 'updateProperty']);
        Route::delete('/admin/properties/{property}', [PropertyController::class, 'deleteProperty']);
        Route::post('/admin/properties/{property}/values', [PropertyController::class, 'storePropertyValue']);
        Route::put('/admin/property-values/{value}', [PropertyController::class, 'updatePropertyValue']);
        Route::delete('/admin/property-values/{value}', [PropertyController::class, 'deletePropertyValue']);
        Route::post('/admin/properties/update-counts', [PropertyController::class, 'updateProductCounts']);
        
        // Property Groups Management (admin)
        Route::get('/admin/property-groups', [PropertyController::class, 'getAllPropertyGroups']);
        Route::post('/admin/categories/{category}/property-groups', [PropertyController::class, 'storePropertyGroup']);
        Route::put('/admin/property-groups/{group}', [PropertyController::class, 'updatePropertyGroup']);
        Route::delete('/admin/property-groups/{group}', [PropertyController::class, 'deletePropertyGroup']);
        
        // Categories (admin)
        Route::get('/admin/categories', [CategoryController::class, 'adminIndex']);
        Route::post('/admin/categories', [CategoryController::class, 'store']);
        Route::put('/admin/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/admin/categories/{category}', [CategoryController::class, 'destroy']);
        Route::get('/admin/categories/{category}/properties', [CategoryController::class, 'getCategoryProperties']);
        Route::put('/admin/categories/{category}/properties', [CategoryController::class, 'updateProperties']);
        
        // Orders (admin)
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
        Route::get('/orders/stats/overview', [OrderController::class, 'stats']);
        
        // Contact (admin)
        Route::get('/contact', [ContactController::class, 'index']);
        Route::get('/contact/unread-count', [ContactController::class, 'unreadCount']);
        Route::get('/contact/recent', [ContactController::class, 'recent']);
        Route::get('/contact/{contactMessage}', [ContactController::class, 'show']);
        Route::patch('/contact/{contactMessage}/status', [ContactController::class, 'updateStatus']);
        Route::delete('/contact/{contactMessage}', [ContactController::class, 'destroy']);
        Route::get('/contact/stats/overview', [ContactController::class, 'stats']);
        
        // Filters (admin)
        Route::post('/products/{product}/filters', [FilterController::class, 'associateProductFilters']);
        
        // Dashboard
        Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
        Route::get('/dashboard/content-analytics', [DashboardController::class, 'getContentAnalytics']);
        Route::get('/dashboard/charts-data', [DashboardController::class, 'getChartsData']);
        
        // Admin Certifications routes
        Route::apiResource('admin/certifications', CertificationController::class)->except(['index']);
        Route::post('/admin/certifications/upload-image', [CertificationController::class, 'uploadImage']);
        Route::post('/admin/products/{product}/certifications', [CertificationController::class, 'attachToProduct']);
        Route::delete('/admin/products/{product}/certifications/{certification}', [CertificationController::class, 'detachFromProduct']);
        
        // Admin Solutions routes
        Route::get('/admin/solutions', [SolutionController::class, 'adminIndex']);
        Route::get('/admin/solutions/available-products', [SolutionController::class, 'getAvailableProducts']);
        Route::post('/admin/solutions/upload-image', [SolutionController::class, 'uploadImage']);
        Route::apiResource('admin/solutions', SolutionController::class)->except(['index']);
        
        // Admin Product Images routes
        Route::apiResource('admin/products/{product}/images', ProductImageController::class);
        Route::post('/admin/products/{product}/images/{image}/set-primary', [ProductImageController::class, 'setPrimary']);
        
        // Admin Category Images routes
        Route::post('/admin/categories/{category}/upload-image', [CategoryController::class, 'uploadImage']);
        Route::delete('/admin/categories/{category}/delete-image', [CategoryController::class, 'deleteImage']);
        
        // Admin Material Categories routes
        Route::get('/admin/materials/categories', [MaterialCategoryController::class, 'adminIndex']);
        Route::apiResource('admin/materials/categories', MaterialCategoryController::class)->except(['index']);
        
        // Admin Material Groups routes
        Route::get('/admin/materials/groups', [MaterialGroupController::class, 'adminIndex']);
        Route::apiResource('admin/materials/groups', MaterialGroupController::class)->except(['index']);
        
        // Admin Materials routes
        Route::get('/admin/materials', [MaterialController::class, 'adminIndex']);
        Route::apiResource('admin/materials', MaterialController::class)->except(['index']);
        Route::post('/admin/materials/upload-image', [MaterialController::class, 'uploadImage']);
        
        // Admin Product Materials routes
        Route::apiResource('admin/products/{product}/materials', ProductMaterialController::class)->except(['index']);
        Route::post('/admin/products/{product}/materials/reorder', [ProductMaterialController::class, 'reorder']);
        Route::post('/admin/products/{product}/materials/{material}/set-default', [ProductMaterialController::class, 'setDefault']);
        
        // Admin Homepage Content routes
        Route::get('/admin/homepage-content', [HomepageContentController::class, 'adminIndex']);
        Route::post('/admin/homepage-content', [HomepageContentController::class, 'store']);
        Route::get('/admin/homepage-content/{id}', [HomepageContentController::class, 'show']);
        Route::put('/admin/homepage-content/{id}', [HomepageContentController::class, 'update']);
        Route::delete('/admin/homepage-content/{id}', [HomepageContentController::class, 'destroy']);
        Route::post('/admin/homepage-content/reorder', [HomepageContentController::class, 'reorder']);
        Route::post('/admin/homepage-content/upload-video', [HomepageContentController::class, 'uploadVideo']);
        
        // Bulk Import Routes
        Route::prefix('import')->group(function () {
            Route::post('/validate', [ImportController::class, 'validateImport']);
            Route::post('/products', [ImportController::class, 'importProducts']);
            Route::get('/logs', [ImportController::class, 'getImportLogs']);
            Route::get('/logs/{id}', [ImportController::class, 'getImportLogDetails']);
            Route::get('/template', [ImportController::class, 'downloadTemplate']);
        });
    });
}); 