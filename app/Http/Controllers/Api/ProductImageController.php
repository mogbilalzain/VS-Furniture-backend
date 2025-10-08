<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Helpers\ImageHelper;

class ProductImageController extends Controller
{
    /**
     * Get all images for a specific product
     */
    public function index($productId): JsonResponse
    {
        try {
            $product = Product::findOrFail($productId);
            
            $images = $product->activeImages()
                ->select('id', 'image_url', 'alt_text', 'title', 'sort_order', 'is_primary', 'is_featured', 'image_type', 'metadata')
                ->get()
                ->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'image_url' => $image->full_image_url,
                        'alt_text' => $image->alt_text,
                        'title' => $image->title,
                        'sort_order' => $image->sort_order,
                        'is_primary' => $image->is_primary,
                        'is_featured' => $image->is_featured,
                        'image_type' => $image->image_type,
                        'dimensions' => $image->image_dimensions,
                        'size' => $image->formatted_size,
                        'metadata' => $image->metadata,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $images
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch product images',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload multiple images for a product
     */
    public function store(Request $request, $productId): JsonResponse
    {
        try {
            $request->validate([
                'images' => 'required|array|max:10',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp,svg|max:5120',
                'alt_texts' => 'sometimes|array',
                'alt_texts.*' => 'string|max:255',
                'titles' => 'sometimes|array',
                'titles.*' => 'string|max:255',
                'is_primary' => 'sometimes|in:true,false,1,0',
                'image_type' => 'sometimes|string|in:product,variant,detail,gallery'
            ]);

            $product = Product::findOrFail($productId);
            $uploadedImages = [];
            $altTexts = $request->get('alt_texts', []);
            $titles = $request->get('titles', []);
            $imageType = $request->get('image_type', 'product');

            foreach ($request->file('images') as $index => $image) {
                // استخدام النظام الجديد
                $result = ImageHelper::uploadImage($image, 'products', (string)$productId);
                
                if (!$result['success']) {
                    // إذا فشل الرفع، تجاهل هذه الصورة واستمر
                    continue;
                }

                $imageData = $result['data'];

                // Check if this should be primary
                $isPrimaryRequest = $request->get('is_primary', false);
                // Convert string values to boolean
                if (is_string($isPrimaryRequest)) {
                    $isPrimaryRequest = in_array(strtolower($isPrimaryRequest), ['true', '1']);
                }
                
                $shouldBePrimary = ($index === 0 && $isPrimaryRequest) || 
                                 (ProductImage::where('product_id', $productId)->count() === 0);

                // If setting as primary, unset other primary images first
                if ($shouldBePrimary) {
                    ProductImage::where('product_id', $productId)
                        ->update(['is_primary' => false]);
                }

                $productImage = ProductImage::create([
                    'product_id' => $productId,
                    'image_url' => $imageData['url'], // استخدام الرابط الجديد
                    'alt_text' => $altTexts[$index] ?? $product->name . ' - Image ' . ($index + 1),
                    'title' => $titles[$index] ?? $product->name,
                    'is_primary' => $shouldBePrimary,
                    'image_type' => $imageType,
                    'sort_order' => ProductImage::getNextSortOrder($productId),
                    'metadata' => [
                        'width' => $imageData['dimensions']['width'] ?? null,
                        'height' => $imageData['dimensions']['height'] ?? null,
                        'size' => $imageData['size'],
                        'mime_type' => $imageData['mime_type'],
                        'original_name' => $imageData['original_name'],
                        'filename' => $imageData['filename'],
                        'storage_path' => $imageData['path']
                    ]
                ]);

                $uploadedImages[] = [
                    'id' => $productImage->id,
                    'image_url' => $imageData['full_url'],
                    'alt_text' => $productImage->alt_text,
                    'title' => $productImage->title,
                    'sort_order' => $productImage->sort_order,
                    'is_primary' => $productImage->is_primary,
                    'image_type' => $productImage->image_type,
                    'dimensions' => $imageData['dimensions'],
                    'size' => $imageData['size_formatted'],
                    'mime_type' => $imageData['mime_type']
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $uploadedImages,
                'message' => 'Images uploaded successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload images',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update image details
     */
    public function update(Request $request, $productId, $imageId): JsonResponse
    {
        try {
            $request->validate([
                'alt_text' => 'sometimes|string|max:255',
                'title' => 'sometimes|string|max:255',
                'sort_order' => 'sometimes|integer|min:0',
                'is_primary' => 'sometimes|boolean',
                'is_featured' => 'sometimes|boolean',
                'is_active' => 'sometimes|boolean',
                'image_type' => 'sometimes|string|in:product,variant,detail,gallery'
            ]);

            $image = ProductImage::where('product_id', $productId)
                ->where('id', $imageId)
                ->firstOrFail();

            $image->update($request->only([
                'alt_text', 'title', 'sort_order', 'is_primary', 
                'is_featured', 'is_active', 'image_type'
            ]));

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $image->id,
                    'image_url' => $image->full_image_url,
                    'alt_text' => $image->alt_text,
                    'title' => $image->title,
                    'sort_order' => $image->sort_order,
                    'is_primary' => $image->is_primary,
                    'is_featured' => $image->is_featured,
                    'image_type' => $image->image_type,
                ],
                'message' => 'Image updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update image',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an image
     */
    public function destroy($productId, $imageId): JsonResponse
    {
        try {
            $image = ProductImage::where('product_id', $productId)
                ->where('id', $imageId)
                ->firstOrFail();

            $image->delete();

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set an image as primary
     */
    public function setPrimary($productId, $imageId): JsonResponse
    {
        try {
            $image = ProductImage::where('product_id', $productId)
                ->where('id', $imageId)
                ->firstOrFail();

            $image->setPrimary();

            return response()->json([
                'success' => true,
                'message' => 'Primary image updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to set primary image',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}